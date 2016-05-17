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
use Poniverse\Ponyfm\Models\User;
use Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SaveAccountSettingsCommand extends CommandBase
{
    private $_input;
    private $_slug;
    private $_user;
    private $_current;

    function __construct($input, $slug)
    {
        $this->_input = $input;
        $this->_slug = $slug;
        $this->_user = null;
        $this->_current = null;
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        if (Auth::user() != null) {
            $this->_current = Auth::user();

            if ($this->_slug == $this->_current->slug) {
                $this->_user = $this->_current;
            } else {
                $this->_user = User::where('slug', $this->_slug)->whereNull('disabled_at')->first();
            }

            if ($this->_user == null) {
                return false;
            }

            if (Gate::allows('edit', $this->_user)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        if ($this->_user == null) {
            if ($_current->hasRole('admin')) {
                return CommandResponse::fail(['Not found']);
            } else {
                return CommandResponse::fail(['Permission denied']);
            }
        }

        $rules = [
            'display_name' => 'required|min:3|max:26',
            'bio' => 'textarea_length:250'
        ];

        if ($this->_input['sync_names'] == 'true') {
            $this->_input['display_name'] = $this->_user->username;
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
            if ($this->_user->avatar_id == null && !isset($this->_input['avatar']) && !isset($this->_input['avatar_id'])) {
                $validator->messages()->add('avatar',
                    'You must upload or select an avatar if you are not using gravatar!');

                return CommandResponse::fail($validator);
            }
        }

        $this->_user->bio = $this->_input['bio'];
        $this->_user->display_name = $this->_input['display_name'];
        $this->_user->sync_names = $this->_input['sync_names'] == 'true';
        $this->_user->can_see_explicit_content = $this->_input['can_see_explicit_content'] == 'true';
        $this->_user->uses_gravatar = $this->_input['uses_gravatar'] == 'true';

        if ($this->_user->uses_gravatar) {
            $this->_user->avatar_id = null;
            $this->_user->gravatar = $this->_input['gravatar'];
        } else {
            if (isset($this->_input['avatar_id'])) {
                $this->_user->avatar_id = $this->_input['avatar_id'];
            } else {
                if (isset($this->_input['avatar'])) {
                    $this->_user->avatar_id = Image::upload($this->_input['avatar'], $this->_user)->id;
                }
            }
        }

        $this->_user->save();

        return CommandResponse::succeed();
    }
}
