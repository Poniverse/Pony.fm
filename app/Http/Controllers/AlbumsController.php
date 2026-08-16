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

use App\AlbumDownloader;
use App\Models\Album;
use App\Models\ResourceLogItem;
use App\Models\Track;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;

class AlbumsController extends Controller
{
    const PER_PAGE = 40;

    public function getIndex(Request $request)
    {
        $page = max(1, (int) $request->query('page', 1));

        $query = Album::summary()
            ->with('user', 'user.avatar', 'cover')
            ->userDetails()
            // An album with only one track is not really an album.
            ->where('track_count', '>', 1);

        $count = $query->count();
        $query->orderBy('title')->skip(($page - 1) * self::PER_PAGE)->take(self::PER_PAGE);

        return Inertia::render('albums/index', [
            'albums' => $query->get()->map(fn (Album $album) => Album::mapPublicAlbumSummary($album))->all(),
            'currentPage' => $page,
            'totalPages' => (int) ceil($count / self::PER_PAGE),
        ]);
    }

    public function getShow(Request $request, $id, $slug)
    {
        $album = Album::with([
            'tracks' => fn ($query) => $query->userDetails(),
            'tracks.cover',
            'tracks.genre',
            'tracks.user',
            'tracks.user.avatar',
            'tracks.trackFiles',
            'user',
            'user.avatar',
            'comments',
            'comments.user',
        ])
            ->userDetails()
            ->find($id);

        if (! $album) {
            abort(404);
        }

        if ($album->slug != $slug) {
            return Redirect::action([static::class, 'getShow'], [$id, $album->slug]);
        }

        // Views are logged by the client via POST /api/web/views (see
        // TracksController::getTrack for why).

        $mapped = Album::mapPublicAlbumShow($album);
        if ($mapped['is_downloadable'] == 0) {
            unset($mapped['formats']);
        }

        return Inertia::render('albums/show', ['album' => $mapped])
            ->withViewData(['meta' => View::make('meta.album', ['album' => $album])->render()]);
    }

    public function getShortlink($id)
    {
        $album = Album::find($id);
        if (! $album) {
            abort(404);
        }

        return Redirect::action([AlbumsController::class, 'getShow'], [$id, $album->slug]);
    }

    public function getDownload($id, $extension)
    {
        $album = Album::with('tracks', 'tracks.trackFiles', 'user')->find($id);
        if (! $album) {
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

        if (! $album->hasLosslessTracks() && in_array($formatName, Track::$LosslessFormats)) {
            abort(404);
        }

        ResourceLogItem::logItem('album', $id, ResourceLogItem::DOWNLOAD, $format['index']);
        $downloader = new AlbumDownloader($album, $formatName);

        return response()->streamDownload(function () use ($downloader) {
            $downloader->download();
        }, $album->user->display_name.' - '.$album->title.'.zip', [
            'Content-Type' => 'application/zip',
        ]);
    }
}
