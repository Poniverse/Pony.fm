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

namespace App\Http\Controllers\Api\Web;

use Auth;
use File;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Request;
use App\Commands\DeleteTrackCommand;
use App\Commands\EditTrackCommand;
use App\Commands\GenerateTrackFilesCommand;
use App\Commands\UploadTrackCommand;
use App\Http\Controllers\ApiControllerBase;
use App\Jobs\EncodeTrackFile;
use App\Models\Genre;
use App\Models\ResourceLogItem;
use App\Models\Track;
use App\Models\TrackType;
use App\Models\TrackFile;
use App\Models\User;
use Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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

        if ($track->status === Track::STATUS_PROCESSING) {
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
        return $this->execute(new EditTrackCommand($id, Request::all()));
    }

    public function postUploadNewVersion($trackId)
    {
        session_write_close();

        $track = Track::find($trackId);
        if (!$track) {
            return $this->notFound('Track not found!');
        }
        $this->authorize('edit', $track);

        $track->version_upload_status = Track::STATUS_PROCESSING;
        $track->update();
        return $this->execute(new UploadTrackCommand(true, false, null, false, $track->getNextVersion(), $track));
    }

    public function getVersionUploadStatus($trackId)
    {
        $track = Track::findOrFail($trackId);
        $this->authorize('edit', $track);

        if ($track->version_upload_status === Track::STATUS_PROCESSING) {
            return Response::json(['message' => 'Processing...'], 202);
        } elseif ($track->version_upload_status === Track::STATUS_COMPLETE) {
            return Response::json(['message' => 'Processing complete!'], 201);
        } else {
            // something went wrong
            return Response::json(['error' => 'Processing failed!'], 500);
        }
    }

    public function getVersionList($trackId)
    {
        $track = Track::findOrFail($trackId);
        $this->authorize('edit', $track);

        $versions = [];
        $trackFiles = $track->trackFilesForAllVersions()->where('is_master', 'true')->get();
        foreach ($trackFiles as $trackFile) {
            $versions[] = [
                'version' => $trackFile->version,
                'url' => '/tracks/' . $track->id . '/version-change/' . $trackFile->version,
                'created_at' => $trackFile->created_at->timestamp
            ];
        }

        return Response::json(['current_version' => $track->current_version, 'versions' => $versions], 200);
    }

    public function getChangeVersion($trackId, $newVersion)
    {
        $track = Track::find($trackId);
        if (!$track) {
            return $this->notFound('Track not found!');
        }
        $this->authorize('edit', $track);

        $masterTrackFile = $track->trackFilesForVersion($newVersion)->where('is_master', true)->first();
        if (!$masterTrackFile) {
            return $this->notFound('Version not found!');
        }

        $track->version_upload_status = Track::STATUS_PROCESSING;
        $track->update();
        $sourceFile = new UploadedFile($masterTrackFile->getFile(), $masterTrackFile->getFilename());
        return $this->execute(new GenerateTrackFilesCommand($track, $sourceFile, false, false, true, $newVersion));
    }

    public function getShow($id)
    {
        $track = Track::userDetails()->withComments()->find($id);
        if (!$track || !$track->canView(Auth::user())) {
            return $this->notFound('Track not found!');
        }

        if (Request::get('log')) {
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

        if (!$track->canView(Auth::user())) {
                    return $this->notFound('Track not found!');
        }

        if ($track->is_downloadable == false) {
                    return $this->notFound('Track not found!');
        }

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

    public function getIndex($all = false, $unknown = false)
    {
        $page = 1;
        $perPage = 45;

        if (Request::has('page')) {
            $page = Request::get('page');
        }

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

        $this->applyFilters($query, $unknown);
        $totalCount = $query->count();
        $this->applyOrdering($query);

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

    public function getClassifierQueue()
    {
        $this->authorize('access-admin-area');
        return $this->getIndex(true, true);
    }

    public function getOwned(User $user)
    {
        $query = Track::summary()->where('user_id', $user->id)->orderBy('created_at', 'desc');

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
            return $this->notFound('Track '.$id.' not found!');
        }

        $this->authorize('edit', $track);

        return Response::json(Track::mapPrivateTrackShow($track), 200);
    }

    /**
     * To be run after aggregating the total number of tracks for a given query.
     * This is separated from applyFilters() because Postgres doesn't allow
     * ORDER BY statements in a COUNT(*) query that returns a single value.
     *
     * @param $query
     * @return mixed
     */
    private function applyOrdering($query)
    {
        if (Request::has('order')) {
            $order = \Request::get('order');
            $parts = explode(',', $order);
            $query->orderBy($parts[0], $parts[1]);
        }

        return $query;
    }

    /**
     * This should be run before count()'ing a query's results.
     *
     * @param $query
     * @return mixed
     */
    private function applyFilters($query, $unknown = false)
    {
        if (Request::has('is_vocal')) {
            $isVocal = \Request::get('is_vocal');
            if ($isVocal == 'true') {
                $query->whereIsVocal(true);
            } else {
                $query->whereIsVocal(false);
            }
        }

        if (Request::has('in_album')) {
            if (Request::get('in_album') == 'true') {
                $query->whereNotNull('album_id');
            } else {
                $query->whereNull('album_id');
            }
        }

        if (Request::has('genres')) {
            $query->whereIn('genre_id', Request::get('genres'));
        }

        if (Request::has('types') && !$unknown) {
            $query->whereIn('track_type_id', Request::get('types'));
        }

        $archive = null;

        if (Request::has('archive')) {
            // Select which archive to view
            $archive = Request::get('archive');
            $query->where('source', $archive);
        }

        if ($unknown) {
            $query->where(function ($q) {
                $unknownGenre = Genre::where('name', 'Unknown')->first();

                $q->where('track_type_id', TrackType::UNCLASSIFIED_TRACK);

                if ($unknownGenre) {
                    $q->orWhere('genre_id', $unknownGenre->id);
                }
            });

            $archives = ['mlpma', 'ponify', 'eqbeats'];
            $akey = array_search($archive, $archives);

            if (!$akey)
                $query->join($archive . '_tracks', 'tracks.id', '=', $archive . 'tracks.track_id');
        }

        if (Request::has('songs')) {
            // DISTINCT is needed here to avoid duplicate results
            // when a track is associated with multiple show songs.
            $query->distinct();
            $query->join('show_song_track', function ($join) {
                $join->on('tracks.id', '=', 'show_song_track.track_id');
            });
            $query->whereIn('show_song_track.show_song_id', Request::get('songs'));
        }

        return $query;
    }
}
