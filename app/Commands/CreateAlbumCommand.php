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

use App\Models\Album;
use App\Models\Image;
use App\Models\User;
use Auth;
use Gate;
use Validator;

class CreateAlbumCommand extends CommandBase
{
    private $_input;
    /**
     * @var User
     */
    private $_albumOwner;

    public function __construct($input)
    {
        $this->_input = $input;
        $this->_albumOwner = User::find($this->_input['user_id']);
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        return $this->_albumOwner !== null && Gate::allows('create-album', $this->_albumOwner);
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        $rules = [
            'title' => 'required|min:3|max:50',
            'cover' => 'image|mimes:png|min_width:350|min_height:350',
            'cover_id' => 'exists:images,id',
            'track_ids' => 'exists:tracks,id',
            'user_id'   => 'exists:users,id',
        ];

        $validator = Validator::make($this->_input, $rules);

        if ($validator->fails()) {
            return CommandResponse::fail($validator);
        }

        $album = new Album();
        $album->user_id = $this->_albumOwner->id;
        $album->title = $this->_input['title'];
        $album->description = $this->_input['description'];

        if (isset($this->_input['cover_id'])) {
            $album->cover_id = $this->_input['cover_id'];
        } else {
            if (isset($this->_input['cover'])) {
                $cover = $this->_input['cover'];
                $album->cover_id = Image::upload($cover, $this->_albumOwner)->id;
            } else {
                if (isset($this->_input['remove_cover']) && $this->_input['remove_cover'] == 'true') {
                    $album->cover_id = null;
                }
            }
        }

        $trackIds = explode(',', $this->_input['track_ids']);
        $album->save();
        $album->syncTrackIds($trackIds);

        return CommandResponse::succeed(['id' => $album->id]);
    }
}
