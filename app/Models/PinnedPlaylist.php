<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Feld0
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

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\PinnedPlaylist
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $playlist_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Playlist $playlist
 * @method static \Illuminate\Database\Query\Builder|\App\Models\PinnedPlaylist whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\PinnedPlaylist whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\PinnedPlaylist wherePlaylistId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\PinnedPlaylist whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\PinnedPlaylist whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PinnedPlaylist extends Model
{
    protected $table = 'pinned_playlists';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function playlist()
    {
        return $this->belongsTo(Playlist::class);
    }
}
