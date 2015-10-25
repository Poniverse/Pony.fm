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

namespace Poniverse\Ponyfm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{

    use SoftDeletes;

    protected $table = 'comments';

    protected $dates = ['deleted_at'];

    public function user()
    {
        return $this->belongsTo('Poniverse\Ponyfm\User');
    }

    public function track()
    {
        return $this->belongsTo('Poniverse\Ponyfm\Track');
    }

    public function album()
    {
        return $this->belongsTo('Poniverse\Ponyfm\Album');
    }

    public function playlist()
    {
        return $this->belongsTo('Poniverse\Ponyfm\Playlist');
    }

    public function profile()
    {
        return $this->belongsTo('Poniverse\Ponyfm\User', 'profile_id');
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

    public function getResourceAttribute()
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
