<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Feld0.
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

use App\Models\Genre;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class CreateGenreCommand extends CommandBase
{
    /** @var Genre */
    private $_genreName;

    public function __construct($genreName)
    {
        $this->_genreName = $genreName;
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        return Gate::allows('create-genre');
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        $slug = Str::slug($this->_genreName);

        $rules = [
            'name'      => 'required|unique:genres,name,NULL,id,deleted_at,NULL|max:50',
            'slug'      => 'required|unique:genres,slug,NULL,id,deleted_at,NULL',
        ];

        $validator = Validator::make([
            'name' => $this->_genreName,
            'slug' => $slug,
        ], $rules);

        if ($validator->fails()) {
            return CommandResponse::fail($validator);
        }

        Genre::create([
            'name' => $this->_genreName,
            'slug' => $slug,
        ]);

        return CommandResponse::succeed(['message' => 'Genre created!']);
    }
}
