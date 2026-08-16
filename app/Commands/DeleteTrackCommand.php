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

namespace App\Commands;

use App\Jobs\RecountPlaylistTracks;
use App\Models\Track;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DeleteTrackCommand extends CommandBase
{
    use DispatchesJobs;

    /** @var int */
    private $_trackId;

    /** @var Track */
    private $_track;

    public function __construct($trackId)
    {
        $this->_trackId = $trackId;
        $this->_track = Track::find($trackId);
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        return Gate::allows('delete', $this->_track);
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
            $album->track_count = $album->tracks()->count();
            $album->save();
        } else {
            $this->_track->delete();
        }

        // Deleted tracks leave every playlist they were in.
        $playlistIds = DB::table('playlist_track')->where('track_id', $this->_track->id)->pluck('playlist_id');
        if ($playlistIds->isNotEmpty()) {
            DB::table('playlist_track')->where('track_id', $this->_track->id)->delete();
            $this->dispatch(new RecountPlaylistTracks($playlistIds->all()));
        }

        return CommandResponse::succeed();
    }
}
