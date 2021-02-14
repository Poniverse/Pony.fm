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

namespace App\Http\Controllers\Api\Web;

use Illuminate\Http\Request;
use App\Commands\CreateAlbumCommand;
use App\Commands\DeleteAlbumCommand;
use App\Commands\EditAlbumCommand;
use App\Http\Controllers\ApiControllerBase;
use App\Models\Album;
use App\Models\Image;
use App\Models\ResourceLogItem;
use App\Models\Track;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Response;

class AlbumsController extends ApiControllerBase
{
    public function postCreate(Request $request)
    {
        return $this->execute(new CreateAlbumCommand($request->all()));
    }

    public function postEdit(Request $request, $id)
    {
        return $this->execute(new EditAlbumCommand($id, $request->all()));
    }

    public function postDelete($id)
    {
        return $this->execute(new DeleteAlbumCommand($id));
    }

    public function getShow(Request $request, $id)
    {
        $album = Album::with([
            'tracks' => function ($query) {
                $query->userDetails();
            },
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

        if ($request->get('log')) {
            ResourceLogItem::logItem('album', $id, ResourceLogItem::VIEW);
            $album->view_count++;
        }

        $returned_album = Album::mapPublicAlbumShow($album);
        if ($returned_album['is_downloadable'] == 0) {
            unset($returned_album['formats']);
        }

        return response()->json([
            'album' => $returned_album,
        ], 200);
    }

    public function getCachedAlbum($id, $format)
    {
        // Validation
        try {
            /** @var Album $album */
            $album = Album::with('tracks.trackFiles')->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return $this->notFound('Album not found!');
        }

        if (! in_array($format, Track::$CacheableFormats)) {
            return $this->notFound('Format not found!');
        }

        $trackCount = $album->countDownloadableTracks($format);
        $availableFilesCount = $album->countAvailableTrackFiles($format);

        if ($trackCount === $availableFilesCount) {
            $url = $album->getDownloadUrl($format);
        } else {
            $album->encodeCacheableTrackFiles($format);
            $url = null;
        }

        return response()->json(['url' => $url], 200);
    }

    public function getIndex(Request $request)
    {
        $page = 1;
        if ($request->has('page')) {
            $page = $request->get('page');
        }

        $query = Album::summary()
            ->with('user', 'user.avatar', 'cover')
            ->userDetails()
            // An album with only one track is not really an album.
            ->where('track_count', '>', 1);

        $count = $query->count();
        $perPage = 40;

        $query
            ->orderBy('title')
            ->skip(($page - 1) * $perPage)
            ->take($perPage);
        $albums = [];

        foreach ($query->get() as $album) {
            $albums[] = Album::mapPublicAlbumSummary($album);
        }

        return response()->json(
            ['albums' => $albums, 'current_page' => $page, 'total_pages' => ceil($count / $perPage)],
            200
        );
    }

    public function getOwned(User $user)
    {
        $this->authorize('get-albums', $user);

        $query = Album::summary()
            ->with('cover', 'user.avatar')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')->get();
        $albums = [];

        foreach ($query as $album) {
            $albums[] = [
                'id' => $album->id,
                'title' => $album->title,
                'slug' => $album->slug,
                'created_at' => $album->created_at->format('c'),
                'covers' => [
                    'small' => $album->getCoverUrl(Image::SMALL),
                    'normal' => $album->getCoverUrl(Image::NORMAL),
                ],
            ];
        }

        return response()->json($albums, 200);
    }

    public function getEdit(Request $request, $id)
    {
        $album = Album::with('tracks')->find($id);
        if (! $album) {
            return $this->notFound('Album '.$id.' not found!');
        }

        if (Gate::denies('edit', $request->user())) {
            return $this->notAuthorized();
        }

        $tracks = [];
        foreach ($album->tracks as $track) {
            $tracks[] = [
                'id' => $track->id,
                'title' => $track->title,
            ];
        }

        return response()->json([
            'id' => $album->id,
            'title' => $album->title,
            'user_id' => $album->user_id,
            'username' => User::whereId($album->user_id)->first()->username,
            'slug' => $album->slug,
            'created_at' => $album->created_at,
            'published_at' => $album->published_at,
            'description' => $album->description,
            'cover_url' => $album->hasCover() ? $album->getCoverUrl(Image::NORMAL) : null,
            'real_cover_url' => $album->getCoverUrl(Image::NORMAL),
            'tracks' => $tracks,
        ], 200);
    }
}
