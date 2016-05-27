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
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Poniverse\Ponyfm\Models\Follower
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $artist_id
 * @property integer $playlist_id
 * @property string $created_at
 * @property-read \Poniverse\Ponyfm\Models\User $follower
 * @property-read \Poniverse\Ponyfm\Models\User $artist
 */
class Follower extends Model
{
    protected $table = 'followers';

    public $timestamps = false;


    public function follower():BelongsTo {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function artist():BelongsTo {
        return $this->belongsTo(User::class, 'artist_id');
    }
}
