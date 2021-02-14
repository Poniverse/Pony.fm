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

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ResourceLogItem;
use App\Models\Track;
use App\Models\TrackFile;
use Auth;
use Config;
use App;
use Redirect;
use Response;
use View;

class TracksController extends Controller
{
    public function getIndex()
    {
        return View::make('tracks.index');
    }

    public function getEmbed($id)
    {
        $track = Track
            ::whereId($id)
            ->published()
            ->userDetails()
            ->with(
                'user',
                'user.avatar',
                'genre'
            )->first();

        if (!$track || !$track->canView(Auth::user())) {
            App::abort(404);
        }

        $userData = [
            'stats' => [
                'views' => 0,
                'plays' => 0,
                'downloads' => 0
            ],
            'is_favourited' => false
        ];

        if ($track->users->count()) {
            $userRow = $track->users[0];
            $userData = [
                'stats' => [
                    'views' => $userRow->view_count,
                    'plays' => $userRow->play_count,
                    'downloads' => $userRow->download_count,
                ],
                'is_favourited' => $userRow->is_favourited
            ];
        }

        return View::make('tracks.embed', ['track' => $track, 'user' => $userData]);
    }

    public function getOembed(Request $request)
    {
        if (!$request->filled('url')) {
            App::abort(404);
        }

        $parsedUrl = parse_url($request->input('url'));
        $id = explode('-', explode('/', $parsedUrl['path'])[2])[0];

        $track = Track
            ::whereId($id)
            ->published()
            ->userDetails()
            ->first();

        if (!$track || !$track->canView(Auth::user())) {
            App::abort(404);
        }

        $output = [
            'version' => '1.0',
            'type' => 'rich',
            'provider_name' => 'Pony.fm',
            'provider_url' => 'https://pony.fm',
            'width' => 480,
            'height' => 130,
            'title' => $track->title,
            'author_name' => $track->user->display_name,
            'author_url' => $track->user->url,
            'html' => '<iframe src="'.action('TracksController@getEmbed', ['id' => $track->id]).'" width="100%" height="150" allowTransparency="true" frameborder="0" seamless allowfullscreen></iframe>'
        ];

        return Response::json($output);
    }

    public function getTrack($id, $slug)
    {
        $track = Track::find($id);
        if (!$track || !$track->canView(Auth::user())) {
            App::abort(404);
        }

        if ($track->slug != $slug) {
            return Redirect::action('TracksController@getTrack', [$id, $track->slug]);
        }

        return View::make('tracks.show', ['track' => $track]);
    }

    public function getEdit($id, $slug)
    {
        return $this->getTrack($id, $slug);
    }

    public function getShortlink($id)
    {
        $track = Track::find($id);
        if (!$track || !$track->canView(Auth::user())) {
            App::abort(404);
        }

        return Redirect::action('TracksController@getTrack', [$id, $track->slug]);
    }

    public function getStream($id, $extension)
    {
        $track = Track::find($id);
        if (!$track || !$track->canView(Auth::user())) {
            App::abort(404);
        }

        $trackFile = TrackFile::findOrFailByExtension($track->id, $extension);

        $response = Response::make('', 200);
        $filename = $trackFile->getFile();

        if (!file_exists($filename)) {
            App::abort(418);
        }

        ResourceLogItem::logItem('track', $id, ResourceLogItem::PLAY, $trackFile->getFormat()['index']);

        if (Config::get('app.sendfile')) {
            $response->header('X-Sendfile', $filename);
        } else {
            $response->header('X-Accel-Redirect', $filename);
        }

        $time = gmdate(filemtime($filename));

        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $time == $_SERVER['HTTP_IF_MODIFIED_SINCE']) {
            header('HTTP/1.0 304 Not Modified');
            exit();
        }

        $response->header('Last-Modified', $time);
        $response->header('Content-Type', $trackFile->getFormat()['mime_type']);

        return $response;
    }

    public function getDownload($id, $extension)
    {
        $track = Track::find($id);
        if (!$track || !$track->canView(Auth::user())) {
            App::abort(404);
        }

        $trackFile = TrackFile::findOrFailByExtension($track->id, $extension);
        ResourceLogItem::logItem('track', $id, ResourceLogItem::DOWNLOAD, $trackFile->getFormat()['index']);

        $response = Response::make('', 200);
        $filename = $trackFile->getFile();

        if (Config::get('app.sendfile')) {
            $response->header('X-Sendfile', $filename);
            $response->header(
                'Content-Disposition',
                'attachment; filename="'.$trackFile->getDownloadFilename().'"'
            );
        } else {
            $response->header('X-Accel-Redirect', $filename);
            $response->header(
                'Content-Disposition',
                'attachment; filename="'.$trackFile->getDownloadFilename().'"'
            );
        }

        $time = gmdate(filemtime($filename));

        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $time == $_SERVER['HTTP_IF_MODIFIED_SINCE']) {
            header('HTTP/1.0 304 Not Modified');
            exit();
        }

        $response->header('Last-Modified', $time);
        $response->header('Content-Type', $trackFile->getFormat()['mime_type']);

        return $response;
    }
}
