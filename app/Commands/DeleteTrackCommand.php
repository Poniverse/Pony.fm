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

namespace Poniverse\Ponyfm\Commands;

use Poniverse\Ponyfm\Models\Track;

class DeleteTrackCommand extends CommandBase
{
    private $_trackId;
    private $_track;

    function __construct($trackId)
    {
        $this->_trackId = $trackId;
        $this->_track = Track::find($trackId);
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        $user = \Auth::user();

        return $this->_track && $user != null && $this->_track->user_id == $user->id;
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        if ($this->_track->album_id != null) {
            $album = $this->_track->album;
            $this->_track->album_id = null;
            $this->_track->track_number = null;
            $this->_track->delete();
            $album->updateTrackNumbers();
        } else {
            $this->_track->delete();
        }

        return CommandResponse::succeed();
    }
}
