<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Josef Citrine
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
use Illuminate\Foundation\Bus\DispatchesJobs;
use Poniverse\Ponyfm\Models\ShowSong;
use Poniverse\Ponyfm\Jobs\DeleteShowSong;
use Validator;

class DeleteShowSongCommand extends CommandBase
{
    use DispatchesJobs;


    /** @var ShowSong */
    private $_songToDelete;
    private $_destinationSong;

    public function __construct($songId, $destinationSongId) {
        $this->_songToDelete = ShowSong::find($songId);
        $this->_destinationSong = ShowSong::find($destinationSongId);
    }

    /**
     * @return bool
     */
    public function authorize() {
        return Gate::allows('delete', $this->_destinationSong);
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute() {
        $rules = [
            'song_to_delete'    => 'required',
            'destination_song'  => 'required',
        ];

        // The validation will fail if the genres don't exist
        // because they'll be null.
        $validator = Validator::make([
            'song_to_delete' => $this->_songToDelete,
            'destination_song' => $this->_destinationSong,
        ], $rules);


        if ($validator->fails()) {
            return CommandResponse::fail($validator);
        }

        $this->dispatch(new DeleteShowSong($this->_songToDelete, $this->_destinationSong));

        return CommandResponse::succeed(['message' => 'Song deleted!']);
    }
}
