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
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Comment.
 *
 * @property int $id
 * @property int $user_id
 * @property string $ip_address
 * @property string $content
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 * @property int $profile_id
 * @property int $track_id
 * @property int $album_id
 * @property int $playlist_id
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Track $track
 * @property-read \App\Models\Album $album
 * @property-read \App\Models\Playlist $playlist
 * @property-read \App\Models\User $profile
 * @property-read Commentable $resource
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Activity[] $activities
 * @property-read mixed $url
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Comment whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Comment whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Comment whereIpAddress($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Comment whereContent($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Comment whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Comment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Comment whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Comment whereProfileId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Comment whereTrackId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Comment whereAlbumId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Comment wherePlaylistId($value)
 * @mixin \Eloquent
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Comment onlyTrashed()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Comment withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Comment withoutTrashed()
 */
class Comment extends Model
{
    use SoftDeletes;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function track()
    {
        return $this->belongsTo(Track::class);
    }

    public function album()
    {
        return $this->belongsTo(Album::class);
    }

    public function playlist()
    {
        return $this->belongsTo(Playlist::class);
    }

    public function profile()
    {
        return $this->belongsTo(User::class, 'profile_id');
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
                ],
            ],
        ];
    }

    /**
     * @return Commentable
     */
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

    /**
     * Returns the class name of the object that this is a comment on.
     *
     * @return string
     */
    public function getResourceClass():string
    {
        return get_class($this->resource);
    }

    public function delete()
    {
        DB::transaction(function () {
            $this->activities()->delete();
            parent::delete();
        });
    }
}
