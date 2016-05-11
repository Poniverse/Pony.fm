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

use Illuminate\Database\Eloquent\ModelNotFoundException;
use File;
use Poniverse\Ponyfm\Commands\DeleteTrackCommand;
use Poniverse\Ponyfm\Commands\EditTrackCommand;
use Poniverse\Ponyfm\Commands\UploadTrackCommand;
use Poniverse\Ponyfm\Http\Controllers\ApiControllerBase;
use Poniverse\Ponyfm\Jobs\EncodeTrackFile;
use Poniverse\Ponyfm\Models\ResourceLogItem;
use Poniverse\Ponyfm\Models\TrackFile;
use Poniverse\Ponyfm\Models\Track;
use Auth;
use Input;
use Response;

class TracksController extends ApiControllerBase
{
    public function postUpload()
    {
        session_write_close();

        return $this->execute(new UploadTrackCommand(true));
    }

    public function getUploadStatus($trackId)
    {
        $track = Track::findOrFail($trackId);
        $this->authorize('edit', $track);

        if ($track->status === Track::STATUS_PROCESSING){
            return Response::json(['message' => 'Processing...'], 202);

        } elseif ($track->status === Track::STATUS_COMPLETE) {
            return Response::json(['message' => 'Processing complete!'], 201);

        } else {
            // something went wrong
            return Response::json(['error' => 'Processing failed!'], 500);
        }
    }

    public function postDelete($id)
    {
        return $this->execute(new DeleteTrackCommand($id));
    }

    public function postEdit($id)
    {
        return $this->execute(new EditTrackCommand($id, Input::all()));
    }

    public function getShow($id)
    {
        $track = Track::userDetails()->withComments()->find($id);
        if (!$track || !$track->canView(Auth::user())) {
            return $this->notFound('Track not found!');
        }

        if (Input::get('log')) {
            ResourceLogItem::logItem('track', $id, ResourceLogItem::VIEW);
            $track->view_count++;
        }

        $returned_track = Track::mapPublicTrackShow($track);
        if ($returned_track['is_downloadable'] != 1) {
            unset($returned_track['formats']);
        }

        return Response::json(['track' => $returned_track], 200);
    }

    public function getCachedTrack($id, $format)
    {
        // Validation
        try {
            $track = Track::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return $this->notFound('Track not found!');
        }

        if (!$track->canView(Auth::user()))
            return $this->notFound('Track not found!');

        if ($track->is_downloadable == false)
            return $this->notFound('Track not found!');

        if (!in_array($format, Track::$CacheableFormats)) {
            return $this->notFound('Format not found!');
        }

        try {
            $trackFile = $track->trackFiles()->where('format', $format)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->notFound('Track file not found!');
        }

        // Return URL or begin encoding
        if ($trackFile->expires_at != null && File::exists($trackFile->getFile())) {
            $url = $track->getUrlFor($format);
        } elseif ($trackFile->status === TrackFile::STATUS_PROCESSING) {
            $url = null;
        } else {
            $this->dispatch(new EncodeTrackFile($trackFile, true));
            $url = null;
        }

        return Response::json(['url' => $url], 200);
    }

    public function getIndex($all = false)
    {
        $page = 1;
        $perPage = 45;

        if (Input::has('page')) {
            $page = Input::get('page');
        }

        $query = null;

        if ($all) {
            $query = Track::summary()
                ->userDetails()
                ->listed()
                ->explicitFilter()
                ->with('user', 'genre', 'cover', 'album', 'album.user');
        } else {
            $query = Track::summary()
                ->userDetails()
                ->listed()
                ->explicitFilter()
                ->published()
                ->with('user', 'genre', 'cover', 'album', 'album.user');
        }

        $this->applyFilters($query);

        $totalCount = $query->count();
        $query->take($perPage)->skip($perPage * ($page - 1));

        $tracks = [];
        $ids = [];

        foreach ($query->get(['tracks.*']) as $track) {
            $tracks[] = Track::mapPublicTrackSummary($track);
            $ids[] = $track->id;
        }

        return Response::json([
            "tracks" => $tracks,
            "current_page" => $page,
            "total_pages" => ceil($totalCount / $perPage)
        ], 200);
    }

    public function getAllTracks()
    {
        $this->authorize('access-admin-area');
        return $this->getIndex(true);
    }

    public function getOwned()
    {
        $query = Track::summary()->where('user_id', \Auth::user()->id)->orderBy('created_at', 'desc');

        $tracks = [];
        foreach ($query->get() as $track) {
            $tracks[] = Track::mapPrivateTrackSummary($track);
        }

        return Response::json($tracks, 200);
    }

    public function getEdit($id)
    {
        $track = Track::with('showSongs')->find($id);
        if (!$track) {
            return $this->notFound('Track ' . $id . ' not found!');
        }

        $this->authorize('edit', $track);

        return Response::json(Track::mapPrivateTrackShow($track), 200);
    }

    private function applyFilters($query)
    {
        if (Input::has('order')) {
            $order = \Input::get('order');
            $parts = explode(',', $order);
            $query->orderBy($parts[0], $parts[1]);
        }

        if (Input::has('is_vocal')) {
            $isVocal = \Input::get('is_vocal');
            if ($isVocal == 'true') {
                $query->whereIsVocal(true);
            } else {
                $query->whereIsVocal(false);
            }
        }

        if (Input::has('in_album')) {
            if (Input::get('in_album') == 'true') {
                $query->whereNotNull('album_id');
            } else {
                $query->whereNull('album_id');
            }
        }

        if (Input::has('genres')) {
            $query->whereIn('genre_id', Input::get('genres'));
        }

        if (Input::has('types')) {
            $query->whereIn('track_type_id', Input::get('types'));
        }

        if (Input::has('songs')) {
            // DISTINCT is needed here to avoid duplicate results
            // when a track is associated with multiple show songs.
            $query->distinct();
            $query->join('show_song_track', function ($join) {
                $join->on('tracks.id', '=', 'show_song_track.track_id');
            });
            $query->whereIn('show_song_track.show_song_id', Input::get('songs'));
        }

        return $query;
    }
}
