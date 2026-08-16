<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2026 Poniverse.
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

use App\Http\Controllers\ApiControllerBase;
use App\Models\Album;
use App\Models\Playlist;
use App\Models\ResourceLogItem;
use App\Models\Track;
use Illuminate\Http\Request;

class ViewsController extends ApiControllerBase
{
    /**
     * Logs a view of a track, album, or playlist. Called by the client when
     * a show page is actually displayed — the Inertia page request itself
     * can't be used because hover prefetches would inflate the counts and
     * prefetch-cache hits would miss them entirely.
     */
    public function postLogView(Request $request)
    {
        $request->validate([
            'type' => 'required|in:track,album,playlist',
            'id' => 'required|integer',
        ]);

        $type = $request->input('type');
        $id = (int) $request->input('id');

        $resource = match ($type) {
            'track' => Track::find($id),
            'album' => Album::find($id),
            'playlist' => Playlist::find($id),
        };

        if (! $resource || (method_exists($resource, 'canView') && ! $resource->canView($request->user()))) {
            return $this->notFound(ucfirst($type).' not found!');
        }

        ResourceLogItem::logItem($type, $id, ResourceLogItem::VIEW);

        return response()->json(['message' => 'View logged'], 201);
    }
}
