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
use Illuminate\Support\Str;
use Poniverse\Ponyfm\Models\ShowSong;
use Validator;

class CreateShowSongCommand extends CommandBase
{
    /** @var ShowSong */
    private $_songName;

    public function __construct($songName)
    {
        $this->_songName = $songName;
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        return Gate::allows('create-show-song');
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        $slug = Str::slug($this->_songName);

        $rules = [
            'title'      => 'required|unique:show_songs,title,NULL,id,deleted_at,NULL|max:250',
            'slug'       => 'required|unique:show_songs,slug,NULL,id,deleted_at,NULL'
        ];

        $validator = Validator::make([
            'title' => $this->_songName,
            'slug'  => $slug
        ], $rules);

        if ($validator->fails()) {
            return CommandResponse::fail($validator);
        }

        ShowSong::create([
            'title'   => $this->_songName,
            'slug'   => $slug,
            'lyrics' => ''
        ]);

        return CommandResponse::succeed(['message' => 'Song created!']);
    }
}
