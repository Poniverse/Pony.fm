<?php

namespace App\Http\Controllers;

use App\ResourceLogItem;
use App\Track;
use App\TrackFile;
use Auth;
use Config;
use Illuminate\Support\Facades\App;
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

    public function getTrack($id, $slug)
    {
        $track = Track::find($id);
        if (!$track || !$track->canView(Auth::user())) {
            App::abort(404);
        }

        if ($track->slug != $slug) {
            return Redirect::action('TracksController@getTrack', [$id, $track->slug]);
        }

        return View::make('tracks.show');
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
        ResourceLogItem::logItem('track', $id, ResourceLogItem::PLAY, $trackFile->getFormat()['index']);

        $response = Response::make('', 200);
        $filename = $trackFile->getFile();

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
            $response->header('Content-Disposition',
                'attachment; filename="' . $trackFile->getDownloadFilename() . '"');
        } else {
            $response->header('X-Accel-Redirect', $filename);
            $response->header('Content-Disposition',
                'attachment; filename="' . $trackFile->getDownloadFilename() . '"');
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