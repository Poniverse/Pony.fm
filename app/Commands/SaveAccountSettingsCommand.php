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

use Poniverse\Ponyfm\Models\Image;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SaveAccountSettingsCommand extends CommandBase
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
        return Auth::user() != null;
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        $user = Auth::user();

        $rules = [
            'display_name' => 'required|min:3|max:26',
            'bio' => 'textarea_length:250'
        ];

        if ($this->_input['sync_names'] == 'true') {
            $this->_input['display_name'] = $user->username;
        }

        if ($this->_input['uses_gravatar'] == 'true') {
            $rules['gravatar'] = 'email';
        } else {
            $rules['avatar'] = 'image|mimes:png|min_width:350|min_height:350';
            $rules['avatar_id'] = 'exists:images,id';
        }

        $validator = Validator::make($this->_input, $rules);

        if ($validator->fails()) {
            return CommandResponse::fail($validator);
        }

        if ($this->_input['uses_gravatar'] != 'true') {
            if ($user->avatar_id == null && !isset($this->_input['avatar']) && !isset($this->_input['avatar_id'])) {
                $validator->messages()->add('avatar',
                    'You must upload or select an avatar if you are not using gravatar!');

                return CommandResponse::fail($validator);
            }
        }

        $user->bio = $this->_input['bio'];
        $user->display_name = $this->_input['display_name'];
        $user->sync_names = $this->_input['sync_names'] == 'true';
        $user->can_see_explicit_content = $this->_input['can_see_explicit_content'] == 'true';
        $user->uses_gravatar = $this->_input['uses_gravatar'] == 'true';

        if ($user->uses_gravatar) {
            $user->avatar_id = null;
            $user->gravatar = $this->_input['gravatar'];
        } else {
            if (isset($this->_input['avatar_id'])) {
                $user->avatar_id = $this->_input['avatar_id'];
            } else {
                if (isset($this->_input['avatar'])) {
                    $user->avatar_id = Image::upload($this->_input['avatar'], $user)->id;
                }
            }
        }

        $user->save();

        return CommandResponse::succeed();
    }
}
