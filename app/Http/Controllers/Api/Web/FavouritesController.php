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

namespace Poniverse\Ponyfm\Http\Controllers\Api\Web;

use Poniverse\Ponyfm\Models\Album;
use Poniverse\Ponyfm\Commands\ToggleFavouriteCommand;
use Poniverse\Ponyfm\Models\Favourite;
use Poniverse\Ponyfm\Http\Controllers\ApiControllerBase;
use Poniverse\Ponyfm\Models\Playlist;
use Poniverse\Ponyfm\Models\Track;
use Auth;
use Illuminate\Support\Facades\Request;
use Response;

class FavouritesController extends ApiControllerBase
{
    public function postToggle()
    {
        return $this->execute(new ToggleFavouriteCommand(Request::get('type'), Request::get('id')));
    }

    public function getTracks()
    {
        $query = Favourite
            ::whereUserId(Auth::user()->id)
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
                'track.album.user'
            ]);

        $tracks = [];

        foreach ($query->get() as $fav) {
            if ($fav->track == null) { // deleted track
                continue;
            }

            $tracks[] = Track::mapPublicTrackSummary($fav->track);
        }

        return Response::json(["tracks" => $tracks], 200);
    }

    public function getAlbums()
    {
        $query = Favourite
            ::whereUserId(Auth::user()->id)
            ->whereNotNull('album_id')
            ->with([
                'album' => function ($query) {
                    $query->userDetails();
                },
                'album.user',
                'album.user.avatar',
                'album.cover'
            ]);

        $albums = [];

        foreach ($query->get() as $fav) {
            if ($fav->album == null) { // deleted album
                continue;
            }

            $albums[] = Album::mapPublicAlbumSummary($fav->album);
        }

        return Response::json(["albums" => $albums], 200);
    }

    public function getPlaylists()
    {
        $query = Favourite
            ::whereUserId(Auth::user()->id)
            ->whereNotNull('playlist_id')
            ->with([
                'playlist' => function ($query) {
                    $query->userDetails();
                },
                'playlist.user',
                'playlist.user.avatar',
                'playlist.tracks',
                'playlist.tracks.cover'
            ]);

        $playlists = [];

        foreach ($query->get() as $fav) {
            if ($fav->playlist == null) { // deleted playlist
                continue;
            }

            $playlists[] = Playlist::mapPublicPlaylistSummary($fav->playlist);
        }

        return Response::json(["playlists" => $playlists], 200);
    }
}
