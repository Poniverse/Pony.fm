<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Logic.
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

use App\Jobs\UpdateTagsForRenamedShowSong;
use App\Models\ShowSong;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RenameShowSongCommand extends CommandBase
{
    use DispatchesJobs;

    /** @var Song */
    private $_song;
    private $_newName;

    public function __construct($genreId, $newName)
    {
        $this->_song = ShowSong::find($genreId);
        $this->_newName = $newName;
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        return Gate::allows('rename', $this->_song);
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        $slug = Str::slug($this->_newName);

        $rules = [
            'title'      => 'required|unique:show_songs,title,'.$this->_song->id.',id|max:250',
            'slug'      => 'required|unique:show_songs,slug,'.$this->_song->id.',id',
        ];

        $validator = Validator::make([
            'title' => $this->_newName,
            'slug' => $slug,
        ], $rules);

        if ($validator->fails()) {
            return CommandResponse::fail($validator);
        }

        $this->_song->title = $this->_newName;
        $this->_song->slug = $slug;
        $this->_song->save();

        $this->dispatch(new UpdateTagsForRenamedShowSong($this->_song));

        return CommandResponse::succeed(['message' => 'Show song renamed!']);
    }
}
