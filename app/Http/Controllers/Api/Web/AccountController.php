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

namespace App\Http\Controllers\Api\Web;

use Illuminate\Http\Request;
use App\Commands\SaveAccountSettingsCommand;
use App\Http\Controllers\ApiControllerBase;
use App\Models\Image;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

class AccountController extends ApiControllerBase
{
    public function getUser(User $user)
    {
        $this->authorize('edit', $user);

        return response()->json([
            'user' => $user->toArray(),
        ]);
    }

    public function getCurrentUser(Request $request)
    {
        $current_user = $request->user();

        if ($current_user != null) {
            $user = User::where('id', $current_user->id)->whereNull('disabled_at')->first();

            if ($user == null) {
                return response()->json(['error' => 'You are not logged in'], 404);
            }

            return response()->json([
                'id' => $user->id,
                'name' => $user->display_name,
                'slug' => $user->slug,
                'url' => $user->url,
                'is_archived' => $user->is_archived,
                'avatars' => [
                    'small' => $user->getAvatarUrl(Image::SMALL),
                    'normal' => $user->getAvatarUrl(Image::NORMAL),
                ],
                'created_at' => $user->created_at,
            ], 200);
        } else {
            return response()->json(['error' => 'You are not logged in'], 404);
        }
    }

    public function getSettings(Request $request, $slug)
    {
        $user = null;
        $current_user = $request->user();

        if ($current_user != null) {
            if ($slug == $current_user->slug) {
                $user = $current_user;
            } else {
                $user = User::where('slug', $slug)->whereNull('disabled_at')->first();
            }

            if ($user == null) {
                return response()->json(['error' => 'User does not exist'], 404);
            }

            if (Gate::denies('edit', $user)) {
                return response()->json(['error' => 'You cannot do that. So stop trying!'], 403);
            }
        }

        return response()->json([
            'id'  => $user->id,
            'bio' => $user->bio,
            'can_see_explicit_content' => $user->can_see_explicit_content == 1,
            'display_name' => $user->display_name,
            'slug' => $user->slug,
            'username' => $user->username,
            'gravatar' => $user->gravatar ? $user->gravatar : $user->email,
            'avatar_url' => ! $user->uses_gravatar ? $user->getAvatarUrl() : null,
            'uses_gravatar' => $user->uses_gravatar == 1,
            'notification_email' => $user->email,
            'notifications' => $user->getNotificationSettings(),
        ], 200);
    }

    public function postSave(Request $request, User $user)
    {
        return $this->execute(new SaveAccountSettingsCommand($request->all(), $user));
    }
}
