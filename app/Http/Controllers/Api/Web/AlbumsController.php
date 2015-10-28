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
use Poniverse\Ponyfm\Album;
use Poniverse\Ponyfm\Commands\CreateAlbumCommand;
use Poniverse\Ponyfm\Commands\DeleteAlbumCommand;
use Poniverse\Ponyfm\Commands\EditAlbumCommand;
use Poniverse\Ponyfm\Http\Controllers\ApiControllerBase;
use Poniverse\Ponyfm\Image;
use Poniverse\Ponyfm\Jobs\EncodeTrackFile;
use Poniverse\Ponyfm\ResourceLogItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Response;
use Poniverse\Ponyfm\Track;

class AlbumsController extends ApiControllerBase
{
    public function postCreate()
    {
        return $this->execute(new CreateAlbumCommand(Input::all()));
    }

    public function postEdit($id)
    {
        return $this->execute(new EditAlbumCommand($id, Input::all()));
    }

    public function postDelete($id)
    {
        return $this->execute(new DeleteAlbumCommand($id));
    }

    public function getShow($id)
    {
        $album = Album::with([
            'tracks' => function ($query) {
                $query->userDetails();
            },
            'tracks.cover',
            'tracks.genre',
            'tracks.user',
            'user',
            'comments',
            'comments.user'
        ])
            ->userDetails()
            ->find($id);

        if (!$album) {
            App::abort(404);
        }

        if (Input::get('log')) {
            ResourceLogItem::logItem('album', $id, ResourceLogItem::VIEW);
            $album->view_count++;
        }

        $returned_album = Album::mapPublicAlbumShow($album);
        if ($returned_album['is_downloadable'] == 0) {
            unset($returned_album['formats']);
        }

        return Response::json([
            'album' => $returned_album
        ], 200);
    }

    public function getCachedAlbum($id, $format)
    {
        // Validation
        try {
            $album = Album::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return $this->notFound('Album not found!');
        }

        if (!array_key_exists($format, Track::$CacheableFormats)) {
            return $this->notFound('Format not found!');
        }

        $tracks = $album->tracks;

        $trackCount = 0;
        $cachedCount = 0;

        foreach($tracks as $track) {
            if ($track->is_downloadable == false) {
                continue;
            } else {
                $trackCount++;
            }

            try {
                $trackFile = $track->trackFiles()->where('format', $format)->firstOrFail();
            } catch (ModelNotFoundException $e) {
                return $this->notFound('Track file for track ID ' . $track->id . ' not found!');
            }

            if ($trackFile->expiration != null) {
                $cachedCount++;
            } elseif ($trackFile->in_progress != true) {
                $this->dispatch(new EncodeTrackFile($trackFile, true));
            }
        }

        if ($trackCount === $cachedCount) {
            $url = $album->getDownloadUrl($format);
        } else {
            $url = null;
        }

        return Response::json(['url' => $url], 200);
    }

    public function getIndex()
    {
        $page = 1;
        if (Input::has('page')) {
            $page = Input::get('page');
        }

        $query = Album::summary()
            ->with('user', 'user.avatar', 'cover')
            ->userDetails()
            ->orderBy('title', 'asc')
            ->where('track_count', '>', 0);

        $count = $query->count();
        $perPage = 40;

        $query->skip(($page - 1) * $perPage)->take($perPage);
        $albums = [];

        foreach ($query->get() as $album) {
            $albums[] = Album::mapPublicAlbumSummary($album);
        }

        return Response::json(["albums" => $albums, "current_page" => $page, "total_pages" => ceil($count / $perPage)],
            200);
    }

    public function getOwned()
    {
        $query = Album::summary()->where('user_id', \Auth::user()->id)->orderBy('created_at', 'desc')->get();
        $albums = [];
        foreach ($query as $album) {
            $albums[] = [
                'id' => $album->id,
                'title' => $album->title,
                'slug' => $album->slug,
                'created_at' => $album->created_at,
                'covers' => [
                    'small' => $album->getCoverUrl(Image::SMALL),
                    'normal' => $album->getCoverUrl(Image::NORMAL)
                ]
            ];
        }

        return Response::json($albums, 200);
    }

    public function getEdit($id)
    {
        $album = Album::with('tracks')->find($id);
        if (!$album) {
            return $this->notFound('Album ' . $id . ' not found!');
        }

        if ($album->user_id != Auth::user()->id) {
            return $this->notAuthorized();
        }

        $tracks = [];
        foreach ($album->tracks as $track) {
            $tracks[] = [
                'id' => $track->id,
                'title' => $track->title
            ];
        }

        return Response::json([
            'id' => $album->id,
            'title' => $album->title,
            'user_id' => $album->user_id,
            'slug' => $album->slug,
            'created_at' => $album->created_at,
            'published_at' => $album->published_at,
            'description' => $album->description,
            'cover_url' => $album->hasCover() ? $album->getCoverUrl(Image::NORMAL) : null,
            'real_cover_url' => $album->getCoverUrl(Image::NORMAL),
            'tracks' => $tracks
        ], 200);
    }
}
