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

namespace Poniverse\Ponyfm\Http\Controllers\Api\Web;

use Poniverse\Ponyfm\Http\Controllers\ApiControllerBase;
use Poniverse\Ponyfm\Commands\SaveAccountSettingsCommand;
use Cover;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Response;

class AccountController extends ApiControllerBase
{
    public function getSettings()
    {
        $user = Auth::user();

        return Response::json([
            'bio' => $user->bio,
            'can_see_explicit_content' => $user->can_see_explicit_content == 1,
            'display_name' => $user->display_name,
            'sync_names' => $user->sync_names == 1,
            'username' => $user->username,
            'gravatar' => $user->gravatar ? $user->gravatar : $user->email,
            'avatar_url' => !$user->uses_gravatar ? $user->getAvatarUrl() : null,
            'uses_gravatar' => $user->uses_gravatar == 1
        ], 200);
    }

    public function postSave()
    {
        return $this->execute(new SaveAccountSettingsCommand(Input::all()));
    }
}
