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

namespace Poniverse\Ponyfm\Http\Controllers;

use App;
use Poniverse\Ponyfm\Models\User;
use View;
use Redirect;

class ArtistsController extends Controller
{
    public function getIndex()
    {
        return View::make('artists.index');
    }

    public function getProfile($slug)
    {
        $user = User::whereSlug($slug)->whereNull('disabled_at')->first();
        if (!$user) {
            App::abort('404');
        }

        return View::make('artists.profile');
    }

    public function getShortlink($id)
    {
        $user = User::find($id);
        if (!$user || $user->disabled_at !== NULL) {
            App::abort('404');
        }

        return Redirect::action('ArtistsController@getProfile', [$user->slug]);
    }
}
