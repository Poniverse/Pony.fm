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

use App\Jobs\UpdateTagsForRenamedGenre;
use App\Models\Genre;
use Gate;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Str;
use Validator;

class RenameGenreCommand extends CommandBase
{
    use DispatchesJobs;

    /** @var Genre */
    private $_genre;
    private $_newName;

    public function __construct($genreId, $newName)
    {
        $this->_genre = Genre::find($genreId);
        $this->_newName = $newName;
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        return Gate::allows('rename', $this->_genre);
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        $slug = Str::slug($this->_newName);

        $rules = [
            'name'      => 'required|unique:genres,name,'.$this->_genre->id.',id,deleted_at,NULL|max:50',
            'slug'      => 'required|unique:genres,slug,'.$this->_genre->id.',id,deleted_at,NULL',
        ];

        $validator = Validator::make([
            'name' => $this->_newName,
            'slug' => $slug,
        ], $rules);

        if ($validator->fails()) {
            return CommandResponse::fail($validator);
        }

        $this->_genre->name = $this->_newName;
        $this->_genre->slug = $slug;
        $this->_genre->save();

        $this->dispatch(new UpdateTagsForRenamedGenre($this->_genre));

        return CommandResponse::succeed(['message' => 'Genre renamed!']);
    }
}
