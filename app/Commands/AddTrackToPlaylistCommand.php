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

use App\Models\Playlist;
use App\Models\Track;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AddTrackToPlaylistCommand extends CommandBase
{
    /** @var Track */
    private $_track;

    /** @var Playlist */
    private $_playlist;

    public function __construct($playlistId, $trackId)
    {
        $this->_playlist = Playlist::find($playlistId);
        $this->_track = Track::find($trackId);
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        $user = Auth::user();

        return $user != null && $this->_playlist && $this->_track && $this->_playlist->user_id == $user->id;
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        // check if this track is already in the playlist
        $validator = Validator::make(
            ['track_id' => $this->_track->id],
            ['track_id' => "unique:playlist_track,track_id,null,id,playlist_id,{$this->_playlist->id}"]
        );

        if ($validator->fails()) {
            return CommandResponse::fail($validator);
        }

        $songIndex = $this->_playlist->trackCount() + 1;
        $this->_playlist->tracks()->attach($this->_track, ['position' => $songIndex]);
        $this->_playlist->touch();

        Playlist::where('id', $this->_playlist->id)->update([
            'track_count' => DB::raw('(SELECT COUNT(id) FROM playlist_track WHERE playlist_id = '.$this->_playlist->id.')'),
        ]);

        return CommandResponse::succeed(['message' => 'Track added!']);
    }
}
