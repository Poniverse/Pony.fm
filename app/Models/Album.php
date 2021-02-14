<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Feld0.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Models;

use App\Contracts\Commentable;
use App\Contracts\Favouritable;
use App\Contracts\Searchable;
use App\Exceptions\TrackFileNotFoundException;
use App\Traits\IndexedInElasticsearchTrait;
use App\Traits\SlugTrait;
use App\Traits\TrackCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Gate;
use Helpers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Venturecraft\Revisionable\RevisionableTrait;

/**
 * App\Models\Album.
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $slug
 * @property string $description
 * @property int $cover_id
 * @property int $track_count
 * @property int $view_count
 * @property int $download_count
 * @property int $favourite_count
 * @property int $comment_count
 * @property \Carbon\Carbon $created_at
 * @property string $updated_at
 * @property \Carbon\Carbon $deleted_at
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ResourceUser[] $users
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Favourite[] $favourites
 * @property-read \App\Models\Image $cover
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Track[] $tracks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Comment[] $comments
 * @property-read mixed $url
 * @property-read \Illuminate\Database\Eloquent\Collection|\Venturecraft\Revisionable\Revision[] $revisionHistory
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Album userDetails()
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Activity[] $activities
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Album whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Album whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Album whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Album whereSlug($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Album whereDescription($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Album whereCoverId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Album whereTrackCount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Album whereViewCount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Album whereDownloadCount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Album whereFavouriteCount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Album whereCommentCount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Album whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Album whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Album whereDeletedAt($value)
 * @mixin \Eloquent
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Album onlyTrashed()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Album withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Album withoutTrashed()
 */
class Album extends Model implements Searchable, Commentable, Favouritable
{
    use HasFactory;
    use SoftDeletes, SlugTrait, TrackCollection, RevisionableTrait, IndexedInElasticsearchTrait;

    protected $elasticsearchType = 'album';

    protected $fillable = ['user_id', 'title', 'slug'];

    public static function summary()
    {
        return self::select(
            'id',
            'title',
            'user_id',
            'slug',
            'created_at',
            'cover_id',
            'comment_count',
            'download_count',
            'view_count',
            'favourite_count'
        );
    }

    public function scopeUserDetails($query)
    {
        if (Auth::check()) {
            $query->with([
                'users' => function ($query) {
                    $query->whereUserId(Auth::user()->id);
                },
            ]);
        }

        return $query;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function users()
    {
        return $this->hasMany(ResourceUser::class);
    }

    public function favourites():HasMany
    {
        return $this->hasMany(Favourite::class);
    }

    public function cover()
    {
        return $this->belongsTo(Image::class);
    }

    public function tracks()
    {
        return $this->hasMany(Track::class)->orderBy('track_number');
    }

    public function trackFiles()
    {
        $trackIds = $this->tracks->pluck('id');

        return TrackFile::join('tracks', 'tracks.current_version', '=', 'track_files.version')->whereIn('track_id', $trackIds);
    }

    public function comments():HasMany
    {
        return $this->hasMany(Comment::class)->orderByDesc('created_at');
    }

    public function activities():MorphMany
    {
        return $this->morphMany(Activity::class, 'resource');
    }

    public static function mapPublicAlbumShow(self $album)
    {
        $tracks = [];
        foreach ($album->tracks as $track) {
            $tracks[] = Track::mapPublicTrackSummary($track);
        }

        $formats = [];
        foreach (Track::$Formats as $name => $format) {
            if (in_array($name, Track::$LosslessFormats) && ! $album->hasLosslessTracksOnly() && ! $album->hasLosslessTracks()) {
                continue;
            }

            $formats[] = [
                'name' => $name,
                'extension' => $format['extension'],
                'url' => $album->getDownloadUrl($name),
                'size' => Helpers::formatBytes($album->getFilesize($name)),
                'isCacheable' => (in_array($name, Track::$CacheableFormats) ? true : false),
                'isMixedLosslessness' => (in_array($name, Track::$LosslessFormats) && ! $album->hasLosslessTracksOnly() && $album->hasLosslessTracks()),
            ];
        }

        $comments = [];
        foreach ($album->comments as $comment) {
            $comments[] = Comment::mapPublic($comment);
        }

        $is_downloadable = 0;
        foreach ($album->tracks as $track) {
            if ($track->is_downloadable == 1) {
                $is_downloadable = 1;
                break;
            }
        }

        $data = self::mapPublicAlbumSummary($album);
        $data['tracks'] = $tracks;
        $data['comments'] = $comments;
        $data['formats'] = $formats;
        $data['description'] = $album->description;
        $data['is_downloadable'] = $is_downloadable;
        $data['share'] = [
            'url' => action('AlbumsController@getShortlink', ['id' => $album->id]),
            'tumblrUrl' => 'http://www.tumblr.com/share/link?url='.urlencode($album->url).'&name='.urlencode($album->title).'&description='.urlencode($album->description),
            'twitterUrl' => 'https://platform.twitter.com/widgets/tweet_button.html?text='.$album->title.' by '.$album->user->display_name.' on Pony.fm',
        ];

        return $data;
    }

    public static function mapPublicAlbumSummary(self $album)
    {
        $userData = [
            'stats' => [
                'views' => 0,
                'downloads' => 0,
            ],
            'is_favourited' => false,
        ];

        if (Auth::check() && $album->users->count()) {
            $userRow = $album->users[0];
            $userData = [
                'stats' => [
                    'views' => (int) $userRow->view_count,
                    'downloads' => (int) $userRow->download_count,
                ],
                'is_favourited' => (bool) $userRow->is_favourited,
            ];
        }

        return [
            'id' => (int) $album->id,
            'track_count' => (int) $album->track_count,
            'title' => $album->title,
            'slug' => $album->slug,
            'created_at' => $album->created_at->format('c'),
            'stats' => [
                'views' => (int) $album->view_count,
                'downloads' => (int) $album->download_count,
                'comments' => (int) $album->comment_count,
                'favourites' => (int) $album->favourite_count,
            ],
            'covers' => [
                'small' => $album->getCoverUrl(Image::SMALL),
                'normal' => $album->getCoverUrl(Image::NORMAL),
                'original' => $album->getCoverUrl(Image::ORIGINAL),
            ],
            'url' => $album->url,
            'user' => [
                'id' => (int) $album->user->id,
                'name' => $album->user->display_name,
                'slug' => $album->user->slug,
                'url' => $album->user->url,
            ],
            'user_data' => $userData,
            'permissions' => [
                'delete' => Gate::allows('delete', $album),
                'edit' => Gate::allows('edit', $album),
            ],
        ];
    }

    public function hasCover()
    {
        return $this->cover_id != null;
    }

    public function getUrlAttribute()
    {
        return action('AlbumsController@getShow', ['id' => $this->id, 'slug' => $this->slug]);
    }

    public function getDownloadUrl($format)
    {
        return action('AlbumsController@getDownload', ['id' => $this->id, 'extension' => Track::$Formats[$format]['extension']]);
    }

    public function getCoverUrl($type = Image::NORMAL)
    {
        if (! $this->hasCover()) {
            return $this->user->getAvatarUrl($type);
        }

        return $this->cover->getUrl($type);
    }

    public function getDirectory()
    {
        $dir = (string) (floor($this->id / 100) * 100);

        return config('ponyfm.files_directory').'/tracks/'.$dir;
    }

    public function getDates()
    {
        return ['created_at', 'deleted_at', 'published_at'];
    }

    public function getFilenameFor($format)
    {
        if (! isset(Track::$Formats[$format])) {
            throw new Exception("$format is not a valid format!");
        }

        $format = Track::$Formats[$format];

        return "{$this->id}.{$format['extension']}.zip";
    }

    public function updateTrackNumbers()
    {
        $tracks = Track::whereAlbumId($this->id)->get();
        $index = 1;

        foreach ($tracks as $track) {
            /** @var $track Track */
            $track->track_number = $index;
            $index++;
            $track->updateTags();
            $track->save();
        }
    }

    public function syncTrackIds($trackIds)
    {
        $trackIdsInAlbum = [];
        foreach ($this->tracks as $track) {
            $trackIdsInAlbum[] = $track->id;
        }

        $trackIdsCount = count($trackIds);
        $trackIdsInAlbumCount = count($trackIdsInAlbum);
        $isSame = true;

        if ($trackIdsInAlbumCount != $trackIdsCount) {
            $isSame = false;
        } else {
            for ($i = 0; $i < $trackIdsInAlbumCount; $i++) {
                if ($i >= $trackIdsCount || $trackIdsInAlbum[$i] != $trackIds[$i]) {
                    $isSame = false;
                    break;
                }
            }
        }

        if ($isSame) {
            return;
        }

        $index = 1;
        $tracksToRemove = [];
        $albumsToFix = [];

        foreach ($this->tracks as $track) {
            $tracksToRemove[$track->id] = $track;
        }

        foreach ($trackIds as $trackId) {
            if (! strlen(trim($trackId))) {
                continue;
            }

            /** @var $track Track */
            $track = Track::find($trackId);

            if ($track->album_id != null && $track->album_id != $this->id) {
                $albumsToFix[] = $track->album;
            }

            $track->album_id = $this->id;
            $track->track_number = $index;
            $track->updateTags();
            $track->save();

            unset($tracksToRemove[$track->id]);
            $index++;
        }

        foreach ($tracksToRemove as $track) {
            /** @var $track Track */
            $track->album_id = null;
            $track->track_number = null;
            $track->updateTags();
            $track->save();
        }

        foreach ($albumsToFix as $album) {
            /** @var $album Album */
            $album->updateTrackNumbers();
        }

        foreach (Track::$Formats as $name => $format) {
            Cache::forget($this->getCacheKey('filesize'.$name));
        }
    }

    /**
     * @param string $key
     * @return string
     */
    public function getCacheKey($key)
    {
        return 'album-'.$this->id.'-'.$key;
    }

    /**
     * The number of tracks in an album will always be in sync.
     *
     * @param array $options
     * @return bool
     */
    public function save(array $options = [])
    {
        $this->recountTracks();

        return parent::save($options);
    }

    public function delete()
    {
        DB::transaction(function () {
            $this->activities()->delete();
            parent::delete();
        });
    }

    protected function recountTracks()
    {
        $this->track_count = $this->tracks->count();
    }

    /**
     * Returns this model in Elasticsearch-friendly form. The array returned by
     * this method should match the current mapping for this model's ES type.
     *
     * @return array
     */
    public function toElasticsearch():array
    {
        return [
            'title' => $this->title,
            'artist' => $this->user->display_name,
            'tracks' => $this->tracks->pluck('title'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function shouldBeIndexed():bool
    {
        return $this->track_count > 0 && ! $this->trashed();
    }

    /**
     * Returns the corresponding resource type ID from the Activity class for
     * this resource.
     *
     * @return string
     */
    public function getResourceType():string
    {
        return 'album';
    }
}
