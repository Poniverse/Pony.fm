<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Feld0.
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

use App\Models\Announcement;
use App\Models\Genre;
use App\Models\ShowSong;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function getIndex()
    {
        return Redirect::to('/admin/genres');
    }

    public function getGenres()
    {
        $genres = Genre::with(['trackCountRelation' => fn ($query) => $query->withTrashed()])
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/genres', ['genres' => $genres->toArray()]);
    }

    public function getTracks()
    {
        return Inertia::render('admin/tracks');
    }

    public function getShowSongs()
    {
        $songs = ShowSong::with('trackCountRelation')
            ->orderBy('title')
            ->get();

        return Inertia::render('admin/show-songs', ['songs' => $songs->toArray()]);
    }

    public function getUsers()
    {
        return Inertia::render('admin/users');
    }

    public function getClassifierQueue()
    {
        return Inertia::render('admin/unclassified');
    }

    public function getAnnouncements()
    {
        return Inertia::render('admin/announcements', [
            'announcements' => Announcement::orderByDesc('start_time')->get()->toArray(),
        ]);
    }
}
