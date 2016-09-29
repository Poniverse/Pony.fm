<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Peter Deltchev
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

namespace Poniverse\Ponyfm\Models;

use DB;
use Helpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Auth;
use Cache;
use Poniverse\Ponyfm\Contracts\Commentable;
use Poniverse\Ponyfm\Contracts\Favouritable;
use Poniverse\Ponyfm\Contracts\Searchable;
use Poniverse\Ponyfm\Exceptions\TrackFileNotFoundException;
use Poniverse\Ponyfm\Traits\IndexedInElasticsearchTrait;
use Poniverse\Ponyfm\Traits\TrackCollection;
use Poniverse\Ponyfm\Traits\SlugTrait;
use Venturecraft\Revisionable\RevisionableTrait;

/**
 * Poniverse\Ponyfm\Models\Playlist
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $title
 * @property string $slug
 * @property string $description
 * @property boolean $is_public
 * @property integer $track_count
 * @property integer $view_count
 * @property integer $download_count
 * @property integer $favourite_count
 * @property integer $follow_count
 * @property integer $comment_count
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\Poniverse\Ponyfm\Models\Track[] $tracks
 * @property-read \Illuminate\Database\Eloquent\Collection|\Poniverse\Ponyfm\Models\ResourceUser[] $users
 * @property-read \Illuminate\Database\Eloquent\Collection|\Poniverse\Ponyfm\Models\Comment[] $comments
 * @property-read \Illuminate\Database\Eloquent\Collection|\Poniverse\Ponyfm\Models\PinnedPlaylist[] $pins
 * @property-read \Poniverse\Ponyfm\Models\User $user
 * @property-read mixed $url
 * @property-read \Illuminate\Database\Eloquent\Collection|\Venturecraft\Revisionable\Revision[] $revisionHistory
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Playlist userDetails()
 * @property-read \Illuminate\Database\Eloquent\Collection|\Poniverse\Ponyfm\Models\Favourite[] $favourites
 * @property-read \Illuminate\Database\Eloquent\Collection|\Poniverse\Ponyfm\Models\Activity[] $activities
 */
class Playlist extends Model implements Searchable, Commentable, Favouritable
{
    use SoftDeletes, SlugTrait, TrackCollection, RevisionableTrait, IndexedInElasticsearchTrait;

    protected $elasticsearchType = 'playlist';

    protected $table = 'playlists';
    protected $dates = ['deleted_at'];
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
                }
            ]);
        }

        return !$query;
    }

    public static function mapPublicPlaylistShow(Playlist $playlist)
    {
        $tracks = [];
        foreach ($playlist->tracks as $track) {
            /** @var $track Track */

            $tracks[] = Track::mapPublicTrackSummary($track);
        }

        $formats = [];
        foreach (Track::$Formats as $name => $format) {
            if (in_array($name, Track::$LosslessFormats) && !$playlist->hasLosslessTracksOnly() && !$playlist->hasLosslessTracks()) {
                continue;
            }
            
            $formats[] = [
                'name' => $name,
                'extension' => $format['extension'],
                'url' => $playlist->getDownloadUrl($name),
                'size' => Helpers::formatBytes($playlist->getFilesize($name)),
                'isCacheable' => (in_array($name, Track::$CacheableFormats) ? true : false),
                'isMixedLosslessness' => (in_array($name, Track::$LosslessFormats) && !$playlist->hasLosslessTracksOnly() && $playlist->hasLosslessTracks())
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
            'twitterUrl' => 'https://platform.twitter.com/widgets/tweet_button.html?text='.$playlist->title.' by '.$playlist->user->display_name.' on Pony.fm'
        ];

        return $data;
    }

    public static function mapPublicPlaylistSummary(Playlist $playlist)
    {
        $userData = [
            'stats' => [
                'views' => 0,
                'downloads' => 0
            ],
            'is_favourited' => false
        ];

        if (Auth::check() && $playlist->users->count()) {
            $userRow = $playlist->users[0];
            $userData = [
                'stats' => [
                    'views' => (int) $userRow->view_count,
                    'downloads' => (int) $userRow->download_count,
                ],
                'is_favourited' => (bool) $userRow->is_favourited
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
                'favourites' => (int) $playlist->favourite_count
            ],
            'covers' => [
                'small' => $playlist->getCoverUrl(Image::SMALL),
                'normal' => $playlist->getCoverUrl(Image::NORMAL),
                'original' => $playlist->getCoverUrl(Image::ORIGINAL)
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
                'edit' => Auth::check() && Auth::user()->id == $playlist->user_id
            ]
        ];
    }

    public function tracks(bool $ordered = true)
    {
        $query = $this
            ->belongsToMany(Track::class)
            ->withPivot('position')
            ->withTimestamps();

        if ($ordered) {
            $query = $query->orderBy('position', 'asc');
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
        return $this->hasMany(Comment::class)->orderBy('created_at', 'desc');
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
        return $this->is_public || ($user != null && $user->id == $this->user_id);
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
     * @inheritdoc
     */
    public function shouldBeIndexed():bool
    {
        return $this->is_public &&
               $this->track_count > 0 &&
               !$this->trashed();
    }

    /**
     * @inheritdoc
     */
    public function getResourceType():string
    {
        return 'playlist';
    }
}
