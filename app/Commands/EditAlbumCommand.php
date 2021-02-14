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

namespace Poniverse\Ponyfm\Commands;

use Auth;
use DB;
use Gate;
use Poniverse\Ponyfm\Models\Album;
use Poniverse\Ponyfm\Models\Image;
use Poniverse\Ponyfm\Models\User;
use Validator;

class EditAlbumCommand extends CommandBase
{
    private $_input;
    /** @var int */
    private $_albumId;
    /** @var Album */
    private $_album;

    public function __construct($trackId, $input)
    {
        $this->_input = $input;
        $this->_albumId = $trackId;
        $this->_album = Album::find($trackId);
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        $user = Auth::user();

        return $this->_album && $user != null && Gate::allows('edit', $this->_album);
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
            'username' => 'exists:users,username',
        ];

        $validator = Validator::make($this->_input, $rules);

        if ($validator->fails()) {
            return CommandResponse::fail($validator);
        }

        $trackIds = explode(',', $this->_input['track_ids']);
        $trackIdsCount = count($trackIds);
        $trackDbCount = DB::table('tracks')->whereIn('id', $trackIds)->count();

        if ($trackDbCount != $trackIdsCount) {
            return CommandResponse::fail('Track IDs invalid');
        }

        $this->_album->title = $this->_input['title'];
        $this->_album->description = $this->_input['description'];

        if (isset($this->_input['cover_id'])) {
            $this->_album->cover_id = $this->_input['cover_id'];
        } else {
            if (isset($this->_input['cover'])) {
                $cover = $this->_input['cover'];
                $this->_album->cover_id = Image::upload($cover, Auth::user())->id;
            } else {
                if (isset($this->_input['remove_cover']) && $this->_input['remove_cover'] == 'true') {
                    $this->_album->cover_id = null;
                }
            }
        }

        if (isset($this->_input['username'])) {
            $newid = User::where('username', $this->_input['username'])->first()->id;

            if ($this->_album->user_id != $newid) {
                $this->_album->user_id = $newid;
            }
        }

        $trackIds = explode(',', $this->_input['track_ids']);
        $this->_album->syncTrackIds($trackIds);
        $this->_album->save();

        return CommandResponse::succeed(['real_cover_url' => $this->_album->getCoverUrl(Image::NORMAL)]);
    }
}
