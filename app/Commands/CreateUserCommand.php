<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Feld0
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

use Gate;
use App\Models\User;
use Validator;

class CreateUserCommand extends CommandBase
{
    private $username;
    private $displayName;
    private $email;
    private $createArchivedUser;
    
    public function __construct(
        string $username,
        string $displayName,
        string $email = null,
        bool $createArchivedUser = false
    ) {
        $this->username = $username;
        $this->displayName = $displayName;
        $this->email = $email;
        $this->createArchivedUser = $createArchivedUser;
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        return Gate::allows('create-user');
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        $rules = [
            'username'              => config('ponyfm.validation_rules.username'),
            'display_name'          => config('ponyfm.validation_rules.display_name'),
            'email'                 => 'email',
            'create_archived_user'  => 'boolean',
        ];

        $validator = Validator::make([
            'username' => $this->username,
            'display_name' => $this->displayName,
        ], $rules);

        if ($validator->fails()) {
            return CommandResponse::fail([
                'message'   => $validator->getMessageBag()->first(),
                'user'      => null
            ]);
        }

        // Attempt to create the user.
        $user = User::findOrCreate($this->username, $this->displayName, $this->email, $this->createArchivedUser);
        if ($user->wasRecentlyCreated) {
            return CommandResponse::succeed([
                'message'   => 'New user successfully created!',
                'user'      => User::mapPublicUserSummary($user)
            ], 201);
        } else {
            return CommandResponse::fail([
                'message'   => 'A user with that username already exists.',
                'user'      => User::mapPublicUserSummary($user)
            ], 409);
        }
    }
}
