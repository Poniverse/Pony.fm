<?php

namespace Api\Mobile;

use App\Http\Controllers\Controller;
use App\Track;
use Response;

class TracksController extends Controller
{
    public function latest()
    {
        $tracks = Track::summary()
            ->userDetails()
            ->listed()
            ->explicitFilter()
            ->published()
            ->with('user', 'genre', 'cover', 'album', 'album.user')->take(10);

        $json = [
            'total_tracks' => $tracks->count(),
            'tracks' => $tracks->toArray()
        ];

        return Response::json($json, 200);
    }

    public function popular()
    {
        $tracks = Track::popular(10)
            ->userDetails()
            ->listed()
            ->explicitFilter()
            ->published()
            ->with('user', 'genre', 'cover', 'album', 'album.user')->take(10);

        $json = [
            'total_tracks' => $tracks->count(),
            'tracks' => $tracks->toArray()
        ];

        return Response::json($json, 200);
    }
}