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

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;

class AccountController extends Controller
{
    public function getIndex(Request $request, $slug)
    {
        $user = User::whereSlug($slug)->whereNull('disabled_at')->firstOrFail();
        Gate::authorize('edit', $user);

        return Inertia::render('account/settings', [
            'accountSlug' => $user->slug,
            'settings' => [
                'id' => $user->id,
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
            ],
        ]);
    }

    public function getRegister()
    {
        return redirect()->to(config('poniverse.urls')['register']);
    }

}
