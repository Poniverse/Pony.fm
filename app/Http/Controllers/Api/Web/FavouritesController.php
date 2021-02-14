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

namespace App\Http\Controllers\Api\Web;

use App\Commands\ToggleFavouriteCommand;
use App\Http\Controllers\ApiControllerBase;
use App\Models\Album;
use App\Models\Favourite;
use App\Models\Playlist;
use App\Models\Track;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class FavouritesController extends ApiControllerBase
{
    public function postToggle(Request $request)
    {
        return $this->execute(new ToggleFavouriteCommand($request->get('type'), $request->get('id')));
    }

    public function getTracks(Request $request)
    {
        $query = Favourite
            ::whereUserId($request->user()->id)
            ->whereNotNull('track_id')
            ->with([
                'track' => function ($query) {
                    $query
                        ->userDetails()
                        ->published();
                },
                'track.user',
                'track.genre',
                'track.cover',
                'track.album',
                'track.album.user',
            ]);

        $tracks = [];

        foreach ($query->get() as $fav) {
            if ($fav->track == null) { // deleted track
                continue;
            }

            $tracks[] = Track::mapPublicTrackSummary($fav->track);
        }

        return response()->json(['tracks' => $tracks], 200);
    }

    public function getAlbums(Request $request)
    {
        $query = Favourite
            ::whereUserId($request->user()->id)
            ->whereNotNull('album_id')
            ->with([
                'album' => function ($query) {
                    $query->userDetails();
                },
                'album.user',
                'album.user.avatar',
                'album.cover',
            ]);

        $albums = [];

        foreach ($query->get() as $fav) {
            if ($fav->album == null) { // deleted album
                continue;
            }

            $albums[] = Album::mapPublicAlbumSummary($fav->album);
        }

        return response()->json(['albums' => $albums], 200);
    }

    public function getPlaylist(Request $request)
    {
        $query = Favourite
            ::whereUserId($request->user()->id)
            ->whereNotNull('playlist_id')
            ->with([
                'playlist' => function ($query) {
                    $query->userDetails();
                },
                'playlist.user',
                'playlist.user.avatar',
                'playlist.tracks',
                'playlist.tracks.cover',
            ]);

        $playlists = [];

        foreach ($query->get() as $fav) {
            if ($fav->playlist == null) { // deleted playlist
                continue;
            }

            $playlists[] = Playlist::mapPublicPlaylistSummary($fav->playlist);
        }

        return response()->json(['playlists' => $playlists], 200);
    }
}
