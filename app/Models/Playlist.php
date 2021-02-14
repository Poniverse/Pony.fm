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
use Helpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Venturecraft\Revisionable\RevisionableTrait;

/**
 * App\Models\Playlist.
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $slug
 * @property string $description
 * @property bool $is_public
 * @property int $track_count
 * @property int $view_count
 * @property int $download_count
 * @property int $favourite_count
 * @property int $follow_count
 * @property int $comment_count
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Track[] $tracks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ResourceUser[] $users
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Comment[] $comments
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PinnedPlaylist[] $pins
 * @property-read \App\Models\User $user
 * @property-read mixed $url
 * @property-read \Illuminate\Database\Eloquent\Collection|\Venturecraft\Revisionable\Revision[] $revisionHistory
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Playlist userDetails()
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Favourite[] $favourites
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Activity[] $activities
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Playlist whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Playlist whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Playlist whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Playlist whereSlug($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Playlist whereDescription($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Playlist whereIsPublic($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Playlist whereTrackCount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Playlist whereViewCount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Playlist whereDownloadCount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Playlist whereFavouriteCount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Playlist whereFollowCount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Playlist whereCommentCount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Playlist whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Playlist whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Playlist whereDeletedAt($value)
 * @mixin \Eloquent
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Playlist onlyTrashed()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Playlist withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Playlist withoutTrashed()
 */
class Playlist extends Model implements Searchable, Commentable, Favouritable
{
    use SoftDeletes, SlugTrait, TrackCollection, RevisionableTrait, IndexedInElasticsearchTrait;

    protected $elasticsearchType = 'playlist';

    protected $table = 'playlists';

    protected $casts = [
        'id'                => 'integer',
        'user_id'           => 'integer',
        'title'             => 'string',
        'description'       => 'string',
        'is_public'         => 'boolean',
        'track_count'       => 'integer',
        'view_count'        => 'integer',
        'download_count'    => 'integer',
        'favourte_count'    => 'integer',
        'follow_count'      => 'integer',
        'comment_count'     => 'integer',
    ];

    public static function summary()
    {
        return self::select(
            'id',
            'title',
            'user_id',
            'slug',
            'created_at',
            'is_public',
            'description',
            'comment_count',
            'download_count',
            'view_count',
            'favourite_count',
            'track_count'
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

    public static function mapPublicPlaylistShow(self $playlist)
    {
        $tracks = [];
        foreach ($playlist->tracks as $track) {
            /** @var $track Track */
            $tracks[] = Track::mapPublicTrackSummary($track);
        }

        $formats = [];
        foreach (Track::$Formats as $name => $format) {
            if (in_array($name, Track::$LosslessFormats) && ! $playlist->hasLosslessTracksOnly() && ! $playlist->hasLosslessTracks()) {
                continue;
            }

            $formats[] = [
                'name' => $name,
                'extension' => $format['extension'],
                'url' => $playlist->getDownloadUrl($name),
                'size' => Helpers::formatBytes($playlist->getFilesize($name)),
                'isCacheable' => (in_array($name, Track::$CacheableFormats) ? true : false),
                'isMixedLosslessness' => (in_array($name, Track::$LosslessFormats) && ! $playlist->hasLosslessTracksOnly() && $playlist->hasLosslessTracks()),
            ];
        }

        $comments = [];
        foreach ($playlist->comments as $comment) {
            $comments[] = Comment::mapPublic($comment);
        }

        $data = self::mapPublicPlaylistSummary($playlist);
        $data['tracks'] = $tracks;
        $data['comments'] = $comments;
        $data['formats'] = $formats;
        $data['share'] = [
            'url' => action('PlaylistsController@getShortlink', ['id' => $playlist->id]),
            'tumblrUrl' => 'http://www.tumblr.com/share/link?url='.urlencode($playlist->url).'&name='.urlencode($playlist->title).'&description='.urlencode($playlist->description),
            'twitterUrl' => 'https://platform.twitter.com/widgets/tweet_button.html?text='.$playlist->title.' by '.$playlist->user->display_name.' on Pony.fm',
        ];

        return $data;
    }

    public static function mapPublicPlaylistSummary(self $playlist)
    {
        $userData = [
            'stats' => [
                'views' => 0,
                'downloads' => 0,
            ],
            'is_favourited' => false,
        ];

        if (Auth::check() && $playlist->users->count()) {
            $userRow = $playlist->users[0];
            $userData = [
                'stats' => [
                    'views' => (int) $userRow->view_count,
                    'downloads' => (int) $userRow->download_count,
                ],
                'is_favourited' => (bool) $userRow->is_favourited,
            ];
        }

        return [
            'id' => (int) $playlist->id,
            'track_count' => $playlist->track_count,
            'title' => $playlist->title,
            'slug' => $playlist->slug,
            'created_at' => $playlist->created_at->format('c'),
            'is_public' => (bool) $playlist->is_public,
            'stats' => [
                'views' => (int) $playlist->view_count,
                'downloads' => (int) $playlist->download_count,
                'comments' => (int) $playlist->comment_count,
                'favourites' => (int) $playlist->favourite_count,
            ],
            'covers' => [
                'small' => $playlist->getCoverUrl(Image::SMALL),
                'normal' => $playlist->getCoverUrl(Image::NORMAL),
                'original' => $playlist->getCoverUrl(Image::ORIGINAL),
            ],
            'url' => $playlist->url,
            'user' => [
                'id' => (int) $playlist->user->id,
                'name' => $playlist->user->display_name,
                'url' => $playlist->user->url,
            ],
            'user_data' => $userData,
            'permissions' => [
                'delete' => Auth::check() && Auth::user()->id == $playlist->user_id,
                'edit' => Auth::check() && Auth::user()->id == $playlist->user_id,
            ],
        ];
    }

    public function tracks(bool $ordered = true)
    {
        $query = $this
            ->belongsToMany(Track::class)
            ->withPivot('position')
            ->withTimestamps();

        if ($ordered) {
            $query = $query->orderBy('position');
        }

        return $query;
    }

    public function trackCount():int
    {
        return $this->tracks(false)->count();
    }

    public function trackFiles()
    {
        $trackIds = $this->tracks->pluck('id');

        return TrackFile::join('tracks', 'tracks.current_version', '=', 'track_files.version')->whereIn('track_id', $trackIds);
    }

    public function users()
    {
        return $this->hasMany(ResourceUser::class);
    }

    public function comments():HasMany
    {
        return $this->hasMany(Comment::class)->orderByDesc('created_at');
    }

    public function pins()
    {
        return $this->hasMany(PinnedPlaylist::class);
    }

    public function favourites():HasMany
    {
        return $this->hasMany(Favourite::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function activities():MorphMany
    {
        return $this->morphMany(Activity::class, 'resource');
    }

    public function hasPinFor($userId)
    {
        foreach ($this->pins as $pin) {
            if ($pin->user_id == $userId) {
                return true;
            }
        }

        return false;
    }

    public function canView($user)
    {
        return $this->is_public || ($user != null && $user->id == $this->user_id) || ($user != null && $user->hasRole('admin'));
    }

    public function getUrlAttribute()
    {
        return action('PlaylistsController@getPlaylist', ['id' => $this->id, 'slug' => $this->slug]);
    }

    public function getDownloadUrl($format)
    {
        return action('PlaylistsController@getDownload', ['id' => $this->id, 'format' => Track::$Formats[$format]['extension']]);
    }

    public function getCoverUrl($type = Image::NORMAL)
    {
        if ($this->tracks->count() == 0) {
            return $this->user->getAvatarUrl($type);
        }

        return $this->tracks[0]->getCoverUrl($type);
    }

    public function pin($userId)
    {
        $pin = new PinnedPlaylist();
        $pin->playlist_id = $this->id;
        $pin->user_id = $userId;
        $pin->save();
    }

    private function getCacheKey($key)
    {
        return 'playlist-'.$this->id.'-'.$key;
    }

    public function delete()
    {
        DB::transaction(function () {
            $this->activities()->delete();
            parent::delete();
        });
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
            'title'     => $this->title,
            'curator'   => $this->user->display_name,
            'tracks'    => $this->tracks->pluck('title'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function shouldBeIndexed():bool
    {
        return $this->is_public &&
               $this->track_count > 0 &&
               ! $this->trashed();
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceType():string
    {
        return 'playlist';
    }
}
