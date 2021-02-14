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

use View;

class AdminController extends Controller
{
    public function getIndex()
    {
        return View::make('shared.null');
    }

    public function getGenres()
    {
        return View::make('shared.null');
    }

    public function getTracks()
    {
        return View::make('shared.null');
    }

    public function getShowSongs()
    {
        return View::make('shared.null');
    }

    public function getUsers()
    {
        return View::make('shared.null');
    }

    public function getClassifierQueue()
    {
        return View::make('shared.null');
    }

    public function getAnnouncements()
    {
        return View::make('shared.null');
    }
}
