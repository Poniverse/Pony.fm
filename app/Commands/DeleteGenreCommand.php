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
use Illuminate\Foundation\Bus\DispatchesJobs;
use Poniverse\Ponyfm\Models\Genre;
use Poniverse\Ponyfm\Jobs\DeleteGenre;
use Validator;

class DeleteGenreCommand extends CommandBase
{
    use DispatchesJobs;


    /** @var Genre */
    private $_genreToDelete;
    private $_destinationGenre;

    public function __construct($genreId, $destinationGenreId) {
        $this->_genreToDelete = Genre::find($genreId);
        $this->_destinationGenre = Genre::find($destinationGenreId);
    }

    /**
     * @return bool
     */
    public function authorize() {
        return Gate::allows('delete', $this->_genreToDelete);
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute() {
        $rules = [
            'genre_to_delete'    => 'required',
            'destination_genre'  => 'required',
        ];

        // The validation will fail if the genres don't exist
        // because they'll be null.
        $validator = Validator::make([
            'genre_to_delete' => $this->_genreToDelete,
            'destination_genre' => $this->_destinationGenre,
        ], $rules);


        if ($validator->fails()) {
            return CommandResponse::fail($validator);
        }

        $this->dispatch(new DeleteGenre($this->_genreToDelete, $this->_destinationGenre));

        return CommandResponse::succeed(['message' => 'Genre deleted!']);
    }
}
