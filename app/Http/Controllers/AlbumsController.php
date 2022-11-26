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
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;

class AlbumsController extends Controller
{
    public function getIndex()
    {
        return view('albums.index');
    }

    public function getShow($id, $slug)
    {
        $album = Album::find($id);
        if (! $album) {
            abort(404);
        }

        if ($album->slug != $slug) {
            return Redirect::action([AlbumsController::class, 'getAlbum'], [$id, $album->slug]);
        }

        return view('albums.show');
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
        $downloader->download();
    }
}
