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

use App\Models\PinnedPlaylist;
use App\Models\Playlist;
use Auth;
use Validator;

class EditPlaylistCommand extends CommandBase
{
    private $_input;
    private $_playlistId;
    private $_playlist;

    public function __construct($playlistId, $input)
    {
        $this->_input = $input;
        $this->_playlistId = $playlistId;
        $this->_playlist = Playlist::find($playlistId);
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        $user = Auth::user();

        return $this->_playlist && $user != null && ($this->_playlist->user_id == $user->id || $user->hasRole('admin'));
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
            'is_pinned' => 'required',
        ];

        $validator = Validator::make($this->_input, $rules);

        if ($validator->fails()) {
            return CommandResponse::fail($validator);
        }

        $this->_playlist->title = $this->_input['title'];
        $this->_playlist->description = $this->_input['description'];
        $this->_playlist->is_public = $this->_input['is_public'] == 'true';

        $this->_playlist->save();

        $pin = PinnedPlaylist::whereUserId(Auth::user()->id)->wherePlaylistId($this->_playlistId)->first();
        if ($pin && $this->_input['is_pinned'] != 'true') {
            $pin->delete();
        } else {
            if (! $pin && $this->_input['is_pinned'] == 'true') {
                $this->_playlist->pin(Auth::user()->id);
            }
        }

        return CommandResponse::succeed([
            'id' => $this->_playlist->id,
            'title' => $this->_playlist->title,
            'slug' => $this->_playlist->slug,
            'created_at' => $this->_playlist->created_at,
            'description' => $this->_playlist->description,
            'url' => $this->_playlist->url,
            'is_pinned' => $this->_input['is_pinned'] == 'true',
            'is_public' => $this->_input['is_public'] == 'true',
        ]);
    }
}
