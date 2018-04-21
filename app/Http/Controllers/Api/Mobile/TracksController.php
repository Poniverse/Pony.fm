<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Feld0
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

namespace Poniverse\Ponyfm\Http\Controllers\Api\Mobile;

use Poniverse\Ponyfm\Http\Controllers\Controller;
use Poniverse\Ponyfm\Models\Track;
use Response;

class TracksController extends Controller
{
    public function latest()
    {
        $tracks = Track::summary()
            ->userDetails()
            ->listed()
            ->explicitFilter()
            ->published()
            ->with('user', 'genre', 'cover', 'album', 'album.user')->take(10)->get();

        $tracks = $tracks->map(function (Track $track) {
            return Track::mapPublicTrackSummary($track);
        });

        $json = [
            'total_tracks' => $tracks->count(),
            'tracks' => $tracks->toArray()
        ];

        return Response::json($json, 200);
    }

    public function popular()
    {
        $tracks = collect(Track::popular(10));

        $json = [
            'total_tracks' => $tracks->count(),
            'tracks' => $tracks->toArray()
        ];

        return Response::json($json, 200);
    }
}
