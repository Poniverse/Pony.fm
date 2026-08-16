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

use App\Models\Track;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;

class StatsController extends Controller
{
    public function getIndex(Request $request, $id, $slug)
    {
        $track = Track::userDetails()->find($id);
        if (! $track || ! $track->canView($request->user())) {
            abort(404);
        }

        if ($track->slug != $slug) {
            return Redirect::action([static::class, 'getIndex'], [$id, $track->slug]);
        }

        return Inertia::render('tracks/stats', [
            'track' => Track::mapPublicTrackSummary($track),
        ])->withViewData(['meta' => View::make('meta.track', ['track' => $track])->render()]);
    }
}
