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

use App\Commands\AddTrackToPlaylistCommand;
use App\Commands\CreatePlaylistCommand;
use App\Commands\DeletePlaylistCommand;
use App\Commands\EditPlaylistCommand;
use App\Commands\RemoveTrackFromPlaylistCommand;
use App\Http\Controllers\ApiControllerBase;
use App\Models\Image;
use App\Models\Playlist;
use App\Models\ResourceLogItem;
use App\Models\Track;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request as RequestF;

class PlaylistsController extends ApiControllerBase
{
    public function postCreate(Request $request)
    {
        return $this->execute(new CreatePlaylistCommand($request->all()));
    }

    public function postEdit(Request $request, $id)
    {
        return $this->execute(new EditPlaylistCommand($id, $request->all()));
    }

    public function postDelete($id)
    {
        return $this->execute(new DeletePlaylistCommand($id));
    }

    public function postAddTrack(Request $request, $id)
    {
        return $this->execute(new AddTrackToPlaylistCommand($id, $request->get('track_id')));
    }

    public function postRemoveTrack(Request $request, $id)
    {
        return $this->execute(new RemoveTrackFromPlaylistCommand($id, $request->get('track_id')));
    }

    public function getIndex(Request $request)
    {
        $page = $request->has('page') ? $request->get('page') : 1;

        $query = Playlist::summary()
            ->with(
                'user',
                'user.avatar',
                'tracks',
                'tracks.cover',
                'tracks.user',
                'tracks.user.avatar',
                'tracks.album',
                'tracks.album.user'
            )
            ->userDetails()
            // A playlist with only one track is not much of a list.
            ->where('track_count', '>', 1)
            ->whereIsPublic(true);

        $count = $query->count();
        $this->applyOrdering($query);

        $perPage = 40;
        $query->skip(($page - 1) * $perPage)->take($perPage);
        $playlists = [];

        foreach ($query->get() as $playlist) {
            $playlists[] = Playlist::mapPublicPlaylistSummary($playlist);
        }

        return response()->json([
            'playlists' => $playlists,
            'current_page' => $page,
            'total_pages' => ceil($count / $perPage),
        ], 200);
    }

    public function getShow(Request $request, $id)
    {
        $playlist = Playlist::with([
            'tracks.user',
            'tracks.genre',
            'tracks.cover',
            'tracks.album',
            'tracks' => function ($query) {
                $query->userDetails();
            },
            'tracks.trackFiles',
            'comments',
            'comments.user',
        ])->userDetails()->find($id);
        if (! $playlist || ! $playlist->canView($request->user())) {
            abort('404');
        }

        if ($request->get('log')) {
            ResourceLogItem::logItem('playlist', $id, ResourceLogItem::VIEW);
            $playlist->view_count++;
        }

        return response()->json(Playlist::mapPublicPlaylistShow($playlist), 200);
    }

    public function getCachedPlaylist(Request $request, $id, $format)
    {
        // Validation
        try {
            /** @var $playlist Playlist */
            $playlist = Playlist::with('tracks.trackFiles')->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return $this->notFound('Playlist not found!');
        }

        if ((! $playlist->is_public && ! $request->user()) || (! $playlist->is_public && ($playlist->user_id !== $request->user()->id))) {
            return $this->notFound('Playlist not found!');
        }

        if (! in_array($format, Track::$CacheableFormats)) {
            return $this->notFound('Format not found!');
        }

        $trackCount = $playlist->countDownloadableTracks($format);
        $availableFilesCount = $playlist->countAvailableTrackFiles($format);

        if ($trackCount === $availableFilesCount) {
            $url = $playlist->getDownloadUrl($format);
        } else {
            $playlist->encodeCacheableTrackFiles($format);
            $url = null;
        }

        return response()->json(['url' => $url], 200);
    }

    public function getPinned(Request $request)
    {
        $query = Playlist
            ::userDetails()
            ->with('tracks', 'tracks.cover', 'tracks.user', 'user')
            ->join('pinned_playlists', function ($join) {
                $join->on('playlist_id', '=', 'playlists.id');
            })
            ->where('pinned_playlists.user_id', '=', $request->user()->id)
            ->orderBy('title')
            ->select('playlists.*')
            ->get();

        $playlists = [];
        foreach ($query as $playlist) {
            $mapped = Playlist::mapPublicPlaylistSummary($playlist);
            $mapped['description'] = $playlist->description;
            $mapped['is_pinned'] = true;
            $playlists[] = $mapped;
        }

        return response()->json($playlists, 200);
    }

    public function getOwned(Request $request, User $user)
    {
        $query = Playlist::summary()
            ->with('pins', 'tracks', 'tracks.cover')
            ->where('user_id', $user->id)
            ->orderBy('title')
            ->get();

        $playlists = [];
        foreach ($query as $playlist) {
            $playlists[] = [
                'id' => $playlist->id,
                'title' => $playlist->title,
                'slug' => $playlist->slug,
                'created_at' => $playlist->created_at,
                'description' => $playlist->description,
                'url' => $playlist->url,
                'covers' => [
                    'small' => $playlist->getCoverUrl(Image::SMALL),
                    'normal' => $playlist->getCoverUrl(Image::NORMAL),
                ],
                'is_pinned' => $playlist->hasPinFor($request->user()->id),
                'is_public' => $playlist->is_public == 1,
                'track_ids' => $playlist->tracks->pluck('id'),
            ];
        }

        return response()->json($playlists, 200);
    }

    /**
     * This function should not deal with anything other than applying order,
     * which is done after the query's total possible results are counted due
     * to Postgres not allowing "ORDER BY" to be combined with "COUNT()".
     *
     * @param $query
     * @return mixed
     */
    private function applyOrdering($query)
    {
        if (RequestF::has('order')) {
            $order = RequestF::get('order');
            $parts = explode(',', $order);
            $query->orderBy($parts[0], $parts[1]);
        }

        return $query;
    }
}
