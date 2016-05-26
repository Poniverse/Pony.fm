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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Poniverse\Ponyfm\Contracts\Commentable;
use Poniverse\Ponyfm\Contracts\GeneratesNotifications;

/**
 * Poniverse\Ponyfm\Models\Comment
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $ip_address
 * @property string $content
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 * @property integer $profile_id
 * @property integer $track_id
 * @property integer $album_id
 * @property integer $playlist_id
 * @property-read \Poniverse\Ponyfm\Models\User $user
 * @property-read \Poniverse\Ponyfm\Models\Track $track
 * @property-read \Poniverse\Ponyfm\Models\Album $album
 * @property-read \Poniverse\Ponyfm\Models\Playlist $playlist
 * @property-read \Poniverse\Ponyfm\Models\User $profile
 * @property-read Commentable $resource
 * @property-read \Illuminate\Database\Eloquent\Collection|\Poniverse\Ponyfm\Models\Activity[] $activities
 * @property-read mixed $url
 */
class Comment extends Model
{

    use SoftDeletes;

    protected $table = 'comments';

    protected $dates = ['deleted_at'];

    public function user()
    {
        return $this->belongsTo('Poniverse\Ponyfm\Models\User');
    }

    public function track()
    {
        return $this->belongsTo('Poniverse\Ponyfm\Models\Track');
    }

    public function album()
    {
        return $this->belongsTo('Poniverse\Ponyfm\Models\Album');
    }

    public function playlist()
    {
        return $this->belongsTo('Poniverse\Ponyfm\Models\Playlist');
    }

    public function profile()
    {
        return $this->belongsTo('Poniverse\Ponyfm\Models\User', 'profile_id');
    }

    public function activities():MorphMany
    {
        return $this->morphMany(Activity::class, 'resource');
    }

    public function getUrlAttribute()
    {
        return $this->resource->url;
    }

    public static function mapPublic($comment)
    {
        return [
            'id' => $comment->id,
            'created_at' => $comment->created_at,
            'content' => $comment->content,
            'user' => [
                'name' => $comment->user->display_name,
                'id' => $comment->user->id,
                'url' => $comment->user->url,
                'avatars' => [
                    'normal' => $comment->user->getAvatarUrl(Image::NORMAL),
                    'thumbnail' => $comment->user->getAvatarUrl(Image::THUMBNAIL),
                    'small' => $comment->user->getAvatarUrl(Image::SMALL),
                ]
            ]
        ];
    }

    public function getResourceAttribute():Commentable
    {
        if ($this->track_id !== null) {
            return $this->track;
        } else {
            if ($this->album_id !== null) {
                return $this->album;
            } else {
                if ($this->playlist_id !== null) {
                    return $this->playlist;
                } else {
                    if ($this->profile_id !== null) {
                        return $this->profile;
                    } else {
                        return null;
                    }
                }
            }
        }
    }
}
