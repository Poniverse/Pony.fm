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

use Gate;
use Poniverse\Ponyfm\Models\Album;
use Auth;

class DeleteAlbumCommand extends CommandBase
{
    /** @var int */
    private $_albumId;

    /** @var Album */
    private $_album;

    public function __construct($albumId)
    {
        $this->_albumId = $albumId;
        $this->_album = Album::find($albumId);
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        return Gate::allows('delete', $this->_album);
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        foreach ($this->_album->tracks as $track) {
            $track->album_id = null;
            $track->track_number = null;
            $track->updateTags();
            $track->save();
        }

        $this->_album->delete();

        return CommandResponse::succeed();
    }
}
