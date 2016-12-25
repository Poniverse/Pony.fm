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
use Poniverse\Ponyfm\Models\User;
use Gate;
use Auth;
use Request;
use Response;

class AccountController extends ApiControllerBase
{
    public function getUser(User $user)
    {
        $this->authorize('edit', $user);

        return Response::json([
            'user' => $user->toArray()
        ]);
    }

    public function getSettings($slug)
    {
        $user = null;
        $current_user = Auth::user();

        if ($current_user != null) {
            if ($slug == $current_user->slug) {
                $user = $current_user;
            } else {
                $user = User::where('slug', $slug)->whereNull('disabled_at')->first();
            }

            if ($user == null) {
                return Response::json(['error' => 'User does not exist'], 404);
            }

            if (Gate::denies('edit', $user)) {
                return Response::json(['error' => 'You cannot do that. So stop trying!'], 403);
            }
        }


        return Response::json([
            'id'  => $user->id,
            'bio' => $user->bio,
            'can_see_explicit_content' => $user->can_see_explicit_content == 1,
            'display_name' => $user->display_name,
            'slug' => $user->slug,
            'username' => $user->username,
            'gravatar' => $user->gravatar ? $user->gravatar : $user->email,
            'avatar_url' => !$user->uses_gravatar ? $user->getAvatarUrl() : null,
            'uses_gravatar' => $user->uses_gravatar == 1,
            // TODO: [#25] Remove this when email notifications are rolled out to everyone.
            'can_manage_notifications' => Gate::forUser($user)->allows('receive-email-notifications'),
            'notifications' => $user->getNotificationSettings()
        ], 200);
    }

    public function postSave(User $user)
    {
        return $this->execute(new SaveAccountSettingsCommand(Request::all(), $user));
    }
}
