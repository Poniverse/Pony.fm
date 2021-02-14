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

namespace Poniverse\Ponyfm\Commands;

use Auth;
use Poniverse\Ponyfm\Models\Playlist;

class DeletePlaylistCommand extends CommandBase
{
    /** @var int */
    private $_playlistId;

    /** @var Playlist */
    private $_playlist;

    public function __construct($playlistId)
    {
        $this->_playlistId = $playlistId;
        $this->_playlist = Playlist::find($playlistId);
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        $user = Auth::user();

        return $this->_playlist && $user != null && $this->_playlist->user_id == $user->id;
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        foreach ($this->_playlist->pins as $pin) {
            $pin->delete();
        }

        $this->_playlist->delete();

        return CommandResponse::succeed();
    }
}
