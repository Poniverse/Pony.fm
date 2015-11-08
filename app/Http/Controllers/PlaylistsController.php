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

namespace Poniverse\Ponyfm\Http\Controllers;

use App;
use Poniverse\Ponyfm\Playlist;
use Poniverse\Ponyfm\ResourceLogItem;
use Poniverse\Ponyfm\Track;
use Poniverse\Ponyfm\PlaylistDownloader;
use Auth;
use Illuminate\Support\Facades\Redirect;
use View;

class PlaylistsController extends Controller
{
    public function getIndex()
    {
        return View::make('playlists.index');
    }

    public function getPlaylist($id, $slug)
    {
        $playlist = Playlist::find($id);
        if (!$playlist || !$playlist->canView(Auth::user())) {
            App::abort(404);
        }

        if ($playlist->slug != $slug) {
            return Redirect::action('PlaylistsController@getPlaylist', [$id, $playlist->slug]);
        }

        return View::make('playlists.show');
    }

    public function getShortlink($id)
    {
        $playlist = Playlist::find($id);
        if (!$playlist || !$playlist->canView(Auth::user())) {
            App::abort(404);
        }

        return Redirect::action('PlaylistsController@getPlaylist', [$id, $playlist->slug]);
    }

    public function getDownload($id, $extension)
    {
        $playlist = Playlist::with('tracks', 'user', 'tracks.album')->find($id);
        if (!$playlist || (!$playlist->is_public && !Auth::check()) || (!$playlist->is_public && ($playlist->user_id !== Auth::user()->id))) {
            App::abort(404);
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
            App::abort(404);
        }

        ResourceLogItem::logItem('playlist', $id, ResourceLogItem::DOWNLOAD, $format['index']);
        $downloader = new PlaylistDownloader($playlist, $formatName);
        $downloader->download();
    }
}
