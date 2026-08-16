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

use App\Models\Playlist;
use App\Models\ResourceLogItem;
use App\Models\Track;
use App\PlaylistDownloader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;

class PlaylistsController extends Controller
{
    const PER_PAGE = 40;

    /** sort key from the URL filter string => [column, direction] */
    const SORTS = [
        'favourites' => ['favourite_count', 'desc'],
        'plays' => ['view_count', 'desc'],
        'downloads' => ['download_count', 'desc'],
        'alphabetical' => ['title', 'asc'],
        'latest' => ['created_at', 'desc'],
        'tracks' => ['track_count', 'desc'],
    ];

    public function getIndex(Request $request)
    {
        $page = max(1, (int) $request->query('page', 1));

        $sort = 'favourites';
        foreach (explode('!', (string) $request->query('filter')) as $part) {
            $tokens = explode('-', $part);
            if ($tokens[0] === 'sort' && isset($tokens[1]) && array_key_exists($tokens[1], self::SORTS)) {
                $sort = $tokens[1];
            }
        }

        $query = Playlist::summary()
            ->with('user', 'user.avatar', 'tracks', 'tracks.cover', 'tracks.user', 'tracks.user.avatar', 'tracks.album', 'tracks.album.user')
            ->userDetails()
            // A playlist with only one track is not much of a list.
            ->where('track_count', '>', 1)
            ->whereIsPublic(true);

        $count = $query->count();
        [$column, $direction] = self::SORTS[$sort];
        $query->orderBy($column, $direction)->skip(($page - 1) * self::PER_PAGE)->take(self::PER_PAGE);

        return Inertia::render('playlists/index', [
            'playlists' => $query->get()->map(fn (Playlist $playlist) => Playlist::mapPublicPlaylistSummary($playlist))->all(),
            'currentPage' => $page,
            'totalPages' => (int) ceil($count / self::PER_PAGE),
            'sort' => $sort,
        ]);
    }

    public function getPlaylist(Request $request, $id, $slug)
    {
        $playlist = Playlist::with([
            'tracks' => fn ($query) => $query->userDetails(),
            'tracks.user',
            'tracks.genre',
            'tracks.cover',
            'tracks.album',
            'tracks.trackFiles',
            'user',
            'user.avatar',
            'comments',
            'comments.user',
        ])
            ->userDetails()
            ->find($id);

        if (! $playlist || ! $playlist->canView($request->user())) {
            abort(404);
        }

        if ($playlist->slug != $slug) {
            return Redirect::action([static::class, 'getPlaylist'], [$id, $playlist->slug]);
        }

        // Views are logged by the client via POST /api/web/views (see
        // TracksController::getTrack for why).

        $mapped = Playlist::mapPublicPlaylistShow($playlist);
        if ($request->user()) {
            $mapped['user_data']['is_pinned'] = $playlist->hasPinFor($request->user()->id);
        }

        return Inertia::render('playlists/show', ['playlist' => $mapped])
            ->withViewData(['meta' => View::make('meta.playlist', ['playlist' => $playlist])->render()]);
    }

    public function getShortlink(Request $request, $id)
    {
        $playlist = Playlist::find($id);
        if (! $playlist || ! $playlist->canView($request->user())) {
            abort(404);
        }

        return Redirect::action([static::class, 'getPlaylist'], [$id, $playlist->slug]);
    }

    public function getDownload(Request $request, $id, $extension)
    {
        $playlist = Playlist::with('tracks', 'tracks.trackFiles', 'user', 'tracks.album')->find($id);
        if (! $playlist || ! $playlist->canView($request->user())) {
            abort(404);
        }

        $format = null;
        $formatName = null;

        foreach (Track::$Formats as $name => $item) {
            if ($item['extension'] == $extension) {
                $format = $item;
                $formatName = $name;
                break;
            }
        }

        if ($format == null) {
            abort(404);
        }

        if (! $playlist->hasLosslessTracks() && in_array($formatName, Track::$LosslessFormats)) {
            abort(404);
        }

        ResourceLogItem::logItem('playlist', $id, ResourceLogItem::DOWNLOAD, $format['index']);
        $downloader = new PlaylistDownloader($playlist, $formatName);
        $user = $request->user();

        return response()->streamDownload(function () use ($downloader, $user) {
            $downloader->download($user);
        }, $playlist->user->display_name.' - '.$playlist->title.'.zip', [
            'Content-Type' => 'application/zip',
        ]);
    }
}
