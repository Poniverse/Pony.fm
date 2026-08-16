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

use App\Models\Album;
use App\Models\Image;
use App\Models\Playlist;
use App\Models\Track;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ContentController extends Controller
{
    public function getTracks(Request $request, $slug, $id = null)
    {
        $user = $this->accountUser($slug);

        $tracks = Track::summary()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Track $track) => Track::mapPrivateTrackSummary($track))
            ->all();

        return Inertia::render('account/tracks', [
            'accountSlug' => $user->slug,
            'tracks' => $tracks,
            'editId' => $id !== null ? (int) $id : null,
        ]);
    }

    public function getAlbums(Request $request, $slug, $id = null)
    {
        $user = $this->accountUser($slug);

        $albums = Album::summary()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Album $album) => [
                'id' => $album->id,
                'title' => $album->title,
                'slug' => $album->slug,
                'url' => $album->url,
                'created_at' => $album->created_at->format('c'),
                'cover_url' => $album->getCoverUrl(Image::SMALL),
                'track_count' => $album->track_count,
            ])
            ->all();

        return Inertia::render('account/albums', [
            'accountSlug' => $user->slug,
            'albums' => $albums,
            'editId' => $id !== null ? (int) $id : null,
            'creating' => str_ends_with($request->path(), '/create'),
        ]);
    }

    public function getPlaylists(Request $request, $slug)
    {
        $user = $this->accountUser($slug);

        $playlists = Playlist::summary()
            ->with('user', 'tracks', 'tracks.cover', 'pins')
            ->userDetails()
            ->where('user_id', $user->id)
            ->orderBy('title')
            ->get()
            ->map(function (Playlist $playlist) {
                $mapped = Playlist::mapPublicPlaylistSummary($playlist);
                $mapped['description'] = $playlist->description;
                $mapped['is_pinned'] = $playlist->pins->isNotEmpty();

                return $mapped;
            })
            ->all();

        return Inertia::render('account/playlists', [
            'accountSlug' => $user->slug,
            'playlists' => $playlists,
        ]);
    }

    private function accountUser(string $slug): User
    {
        $user = User::whereSlug($slug)->whereNull('disabled_at')->firstOrFail();
        Gate::authorize('edit', $user);

        return $user;
    }
}
