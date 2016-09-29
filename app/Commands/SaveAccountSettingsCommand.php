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
use Auth;
use Validator;

class SaveAccountSettingsCommand extends CommandBase
{
    private $_input;

    /** @var User */
    private $_user;

    public function __construct($input, User $user)
    {
        $this->_input = $input;
        $this->_user = $user;
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        return Gate::allows('edit', $this->_user);
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        $rules = [
            'display_name'  => 'required|min:3|max:26',
            'bio'           => 'textarea_length:250',
            'slug'          => [
                'required',
                'unique:users,slug,'.$this->_user->id,
                'min:'.config('ponyfm.user_slug_minimum_length'),
                'regex:/^[a-z\d-]+$/',
                'is_not_reserved_slug'
            ]
        ];

        if ($this->_input['uses_gravatar'] == 'true') {
            $rules['gravatar'] = 'email';
        } else {
            $rules['avatar'] = 'image|mimes:png|min_width:350|min_height:350';
            $rules['avatar_id'] = 'exists:images,id';
        }

        $validator = Validator::make($this->_input, $rules, [
            'slug.regex'  => 'Slugs can only contain numbers, lowercase letters, and dashes.'
        ]);

        if ($validator->fails()) {
            return CommandResponse::fail($validator);
        }

        $this->_user->bio = $this->_input['bio'];
        $this->_user->display_name = $this->_input['display_name'];
        $this->_user->slug = $this->_input['slug'];
        $this->_user->can_see_explicit_content = $this->_input['can_see_explicit_content'] == 'true';
        $this->_user->uses_gravatar = $this->_input['uses_gravatar'] == 'true';

        if ($this->_user->uses_gravatar && !empty($this->_input['gravatar'])) {
            $this->_user->avatar_id = null;
            $this->_user->gravatar = $this->_input['gravatar'];
        } else {
            $this->_user->uses_gravatar = false;

            if (isset($this->_input['avatar_id'])) {
                $this->_user->avatar_id = $this->_input['avatar_id'];
            } elseif (isset($this->_input['avatar'])) {
                $this->_user->avatar_id = Image::upload($this->_input['avatar'], $this->_user)->id;
            } else {
                $this->_user->avatar_id = null;
            }
        }

        $this->_user->save();

        return CommandResponse::succeed();
    }
}
