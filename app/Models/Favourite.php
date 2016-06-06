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

/**
 * Poniverse\Ponyfm\Models\Favourite
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $track_id
 * @property integer $album_id
 * @property integer $playlist_id
 * @property string $created_at
 * @property-read \Poniverse\Ponyfm\Models\User $user
 * @property-read \Poniverse\Ponyfm\Models\Track $track
 * @property-read \Poniverse\Ponyfm\Models\Album $album
 * @property-read \Poniverse\Ponyfm\Models\Playlist $playlist
 * @property-read mixed $resource
 * @property-read mixed $type
 */
class Favourite extends Model
{
    protected $table = 'favourites';
    public $timestamps = false;

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

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

    /**
     * Return the resource associated with this favourite.
     *
     * @return Resource|NULL
     */
    public function getResourceAttribute()
    {
        if ($this->track_id) {
            return $this->track;
        } else {
            if ($this->album_id) {
                return $this->album;
            } else {
                if ($this->playlist_id) {
                    return $this->playlist;
                } // no resource - this should never happen under real circumstances
                else {
                    return null;
                }
            }
        }
    }

    public function getTypeAttribute()
    {
        return get_class($this->resource);
    }
}
