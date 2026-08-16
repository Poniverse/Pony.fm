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

use App\Models\ResourceLogItem;
use App\Models\Track;
use App\Models\TrackFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;
use TrackFilters;

class TracksController extends Controller
{
    public function getIndex(Request $request)
    {
        $path = $request->path();
        $filters = TrackFilters::parse($request->query('filter'));
        $random = $path === 'tracks/random';

        if ($path === 'tracks/popular') {
            $filters['sort'] = 'plays';
        }

        $page = max(1, (int) $request->query('page', 1));
        $listing = TrackFilters::listTracks($filters, $page, $random);

        return Inertia::render('tracks/index', [
            'tracks' => $listing['tracks'],
            'currentPage' => $listing['current_page'],
            'totalPages' => $listing['total_pages'],
            'filters' => $filters,
            'mode' => $path === 'tracks/popular' ? 'popular' : ($random ? 'random' : 'all'),
        ]);
    }

    public function getEmbed(Request $request, $id)
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

        if (! $track || ! $track->canView($request->user())) {
            abort(404);
        }

        $userData = [
            'stats' => [
                'views' => 0,
                'plays' => 0,
                'downloads' => 0,
            ],
            'is_favourited' => false,
        ];

        if ($track->users->count()) {
            $userRow = $track->users[0];
            $userData = [
                'stats' => [
                    'views' => $userRow->view_count,
                    'plays' => $userRow->play_count,
                    'downloads' => $userRow->download_count,
                ],
                'is_favourited' => $userRow->is_favourited,
            ];
        }

        return view('tracks.embed', ['track' => $track, 'user' => $userData]);
    }

    public function getOembed(Request $request)
    {
        if (! $request->filled('url')) {
            abort(404);
        }

        $parsedUrl = parse_url($request->input('url'));
        $id = explode('-', explode('/', $parsedUrl['path'])[2])[0];

        $track = Track
            ::whereId($id)
            ->published()
            ->userDetails()
            ->first();

        if (! $track || ! $track->canView($request->user())) {
            abort(404);
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
            'html' => '<iframe src="'.action([static::class, 'getEmbed'], ['id' => $track->id]).'" width="100%" height="150" allowTransparency="true" frameborder="0" seamless allowfullscreen></iframe>',
        ];

        return response()->json($output);
    }

    public function getTrack(Request $request, $id, $slug)
    {
        $track = Track::userDetails()->withComments()->find($id);
        if (! $track || ! $track->canView($request->user())) {
            abort(404);
        }

        if ($track->slug != $slug) {
            return Redirect::action([static::class, 'getTrack'], [$id, $track->slug]);
        }

        // Views are logged by the client via POST /api/web/views when the page
        // is actually displayed — the page request itself can't be trusted:
        // hover prefetches would inflate counts, and prefetch-cache hits
        // would never reach the server at all.

        $mapped = Track::mapPublicTrackShow($track);
        if ($mapped['is_downloadable'] != 1) {
            unset($mapped['formats']);
        }

        return Inertia::render('tracks/show', ['track' => $mapped])
            ->withViewData(['meta' => View::make('meta.track', ['track' => $track])->render()]);
    }

    public function getEdit(Request $request, $id, $slug)
    {
        $track = Track::with('user')->find($id);
        if (! $track) {
            abort(404);
        }

        return Redirect::to('/'.$track->user->slug.'/account/tracks/edit/'.$track->id);
    }

    public function getShortlink(Request $request, $id)
    {
        $track = Track::find($id);
        if (! $track || ! $track->canView($request->user())) {
            abort(404);
        }

        return Redirect::action([static::class, 'getTrack'], [$id, $track->slug]);
    }

    public function getStream(Request $request, $id, $extension)
    {
        $track = Track::find($id);
        if (! $track || ! $track->canView($request->user())) {
            abort(404);
        }

        $trackFile = TrackFile::findOrFailByExtension($track->id, $extension);

        $filename = $trackFile->getFile();

        if (! file_exists($filename)) {
            abort(418);
        }

        ResourceLogItem::logItem('track', $id, ResourceLogItem::PLAY, $trackFile->getFormat()['index']);

        if (! config('ponyfm.use_sendfile')) {
            return response()->file($filename, [
                'Content-Type' => $trackFile->getFormat()['mime_type'],
            ]);
        }

        $response = response()->noContent(200);
        $response->header('X-Accel-Redirect', $filename);
        $response->header('Content-Type', $trackFile->getFormat()['mime_type']);
        $response->setLastModified(\DateTimeImmutable::createFromFormat('U', (string) filemtime($filename)));
        $response->isNotModified($request);

        return $response;
    }

    public function getDownload(Request $request, $id, $extension)
    {
        $track = Track::find($id);
        if (! $track || ! $track->canView($request->user())) {
            abort(404);
        }

        $trackFile = TrackFile::findOrFailByExtension($track->id, $extension);
        ResourceLogItem::logItem('track', $id, ResourceLogItem::DOWNLOAD, $trackFile->getFormat()['index']);

        $filename = $trackFile->getFile();

        if (! config('ponyfm.use_sendfile')) {
            return response()->download($filename, $trackFile->getDownloadFilename(), [
                'Content-Type' => $trackFile->getFormat()['mime_type'],
            ]);
        }

        $response = response()->noContent(200);
        $response->header('X-Accel-Redirect', $filename);
        $response->header(
            'Content-Disposition',
            'attachment; filename="'.$trackFile->getDownloadFilename().'"'
        );
        $response->setLastModified(\DateTimeImmutable::createFromFormat('U', (string) filemtime($filename)));
        $response->isNotModified($request);

        $response->header('Content-Type', $trackFile->getFormat()['mime_type']);

        return $response;
    }
}
