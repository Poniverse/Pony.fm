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

use Illuminate\Support\Facades\App;
use App\Models\User;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;

class ArtistsController extends Controller
{
    public function getIndex()
    {
        return view('artists.index');
    }

    public function getFavourites($slug)
    {
        return $this->getProfile($slug);
    }

    public function getContent($slug)
    {
        return $this->getProfile($slug);
    }

    public function getProfile($slug)
    {
        $user = User::whereSlug($slug)->first();

        if ($user) {
            if ($user->redirect_to) {
                $newUser = User::find($user->redirect_to);

                if ($newUser) {
                    return Redirect::action('ArtistsController@getProfile', [$newUser->slug]);
                }
            }

            if ($user->disabled_at) {
                abort('404');
            }

            return view('artists.profile');
        } else {
            abort('404');
        }
    }

    public function getShortlink($id)
    {
        $user = User::find($id);
        if (! $user || $user->disabled_at !== null) {
            abort('404');
        }

        return Redirect::action('ArtistsController@getProfile', [$user->slug]);
    }
}
