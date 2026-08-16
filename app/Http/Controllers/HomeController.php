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

use App\Models\Announcement;
use App\Models\Track;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function getIndex(Request $request): Response
    {
        $recentQuery = Track::summary()
            ->with(['genre', 'user', 'cover', 'user.avatar'])
            ->whereIsLatest(true)
            ->listed()
            ->userDetails()
            ->explicitFilter()
            ->published()
            ->orderByDesc('published_at')
            ->whereHas('user', fn ($q) => $q->whereIsArchived(false))
            ->take(12);

        $recentTracks = $recentQuery->get()
            ->map(fn (Track $track) => Track::mapPublicTrackSummary($track))
            ->all();

        $now = Carbon::now();
        $announcement = Announcement::whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->where('start_time', '<', $now)
            ->where('end_time', '>', $now)
            ->orderByDesc('start_time')
            ->first();

        return Inertia::render('home/index', [
            'recentTracks' => $recentTracks,
            'popularTracks' => array_slice(
                Track::popular(30, $request->user()?->can_see_explicit_content ?? false),
                0,
                12
            ),
            'announcement' => $announcement,
        ]);
    }
}
