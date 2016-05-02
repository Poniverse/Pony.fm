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

use Notification;
use Poniverse\Ponyfm\Models\Playlist;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CreatePlaylistCommand extends CommandBase
{
    private $_input;

    function __construct($input)
    {
        $this->_input = $input;
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        $user = \Auth::user();

        return $user != null;
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        $rules = [
            'title' => 'required|min:3|max:50',
            'is_public' => 'required',
            'is_pinned' => 'required'
        ];

        $validator = Validator::make($this->_input, $rules);

        if ($validator->fails()) {
            return CommandResponse::fail($validator);
        }

        $playlist = new Playlist();
        $playlist->user_id = Auth::user()->id;
        $playlist->title = $this->_input['title'];
        $playlist->description = $this->_input['description'];
        $playlist->is_public = $this->_input['is_public'] == 'true';

        $playlist->save();
        
        Notification::publishedNewPlaylist($playlist);

        if ($this->_input['is_pinned'] == 'true') {
            $playlist->pin(Auth::user()->id);
        }

        return CommandResponse::succeed([
            'id' => $playlist->id,
            'title' => $playlist->title,
            'slug' => $playlist->slug,
            'created_at' => $playlist->created_at,
            'description' => $playlist->description,
            'url' => $playlist->url,
            'is_pinned' => $this->_input['is_pinned'] == 'true',
            'is_public' => $this->_input['is_public'] == 'true'
        ]);
    }
}
