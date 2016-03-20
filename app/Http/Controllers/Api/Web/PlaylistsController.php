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
use Poniverse\Ponyfm\Commands\AddTrackToPlaylistCommand;
use Poniverse\Ponyfm\Commands\CreatePlaylistCommand;
use Poniverse\Ponyfm\Commands\DeletePlaylistCommand;
use Poniverse\Ponyfm\Commands\EditPlaylistCommand;
use Poniverse\Ponyfm\Commands\RemoveTrackFromPlaylistCommand;
use Poniverse\Ponyfm\Http\Controllers\ApiControllerBase;
use Poniverse\Ponyfm\Models\Image;
use Poniverse\Ponyfm\Models\Playlist;
use Poniverse\Ponyfm\Models\ResourceLogItem;
use Auth;
use Input;
use Response;
use Poniverse\Ponyfm\Models\Track;

class PlaylistsController extends ApiControllerBase
{
    public function postCreate()
    {
        return $this->execute(new CreatePlaylistCommand(Input::all()));
    }

    public function postEdit($id)
    {
        return $this->execute(new EditPlaylistCommand($id, Input::all()));
    }

    public function postDelete($id)
    {
        return $this->execute(new DeletePlaylistCommand($id, Input::all()));
    }

    public function postAddTrack($id)
    {
        return $this->execute(new AddTrackToPlaylistCommand($id, Input::get('track_id')));
    }

    public function postRemoveTrack($id)
    {
        return $this->execute(new RemoveTrackFromPlaylistCommand($id, Input::get('track_id')));
    }

    public function getIndex()
    {
        $page = 1;
        if (Input::has('page')) {
            $page = Input::get('page');
        }

        $query = Playlist::summary()
            ->with('user', 'user.avatar', 'tracks', 'tracks.cover', 'tracks.user', 'tracks.album', 'tracks.album.user')
            ->userDetails()
            ->orderBy('title', 'asc')
            ->where('track_count', '>', 0)
            ->whereIsPublic(true);

        $count = $query->count();
        $perPage = 40;

        $query->skip(($page - 1) * $perPage)->take($perPage);
        $playlists = [];

        foreach ($query->get() as $playlist) {
            $playlists[] = Playlist::mapPublicPlaylistSummary($playlist);
        }

        return Response::json([
            "playlists" => $playlists,
            "current_page" => $page,
            "total_pages" => ceil($count / $perPage)
        ], 200);
    }

    public function getShow($id)
    {
        $playlist = Playlist::with([
            'tracks.user',
            'tracks.genre',
            'tracks.cover',
            'tracks.album',
            'tracks' => function ($query) {
                $query->userDetails();
            },
            'comments',
            'comments.user'
        ])->userDetails()->find($id);
        if (!$playlist || !$playlist->canView(Auth::user())) {
            App::abort('404');
        }

        if (Input::get('log')) {
            ResourceLogItem::logItem('playlist', $id, ResourceLogItem::VIEW);
            $playlist->view_count++;
        }

        return Response::json(Playlist::mapPublicPlaylistShow($playlist), 200);
    }

    public function getCachedPlaylist($id, $format)
    {
        // Validation
        try {
            /** @var $playlist Playlist */
            $playlist = Playlist::with('tracks.trackFiles')->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return $this->notFound('Playlist not found!');
        }

        if ((!$playlist->is_public && !Auth::check()) || (!$playlist->is_public && ($playlist->user_id !== Auth::user()->id))) {
            return $this->notFound('Playlist not found!');
        }

        if (!in_array($format, Track::$CacheableFormats)) {
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

        return Response::json(['url' => $url], 200);
    }

    public function getPinned()
    {
        $query = Playlist
            ::userDetails()
            ->with('tracks', 'tracks.cover', 'tracks.user', 'user')
            ->join('pinned_playlists', function ($join) {
                $join->on('playlist_id', '=', 'playlists.id');
            })
            ->where('pinned_playlists.user_id', '=', Auth::user()->id)
            ->orderBy('title', 'asc')
            ->select('playlists.*')
            ->get();

        $playlists = [];
        foreach ($query as $playlist) {
            $mapped = Playlist::mapPublicPlaylistSummary($playlist);
            $mapped['description'] = $playlist->description;
            $mapped['is_pinned'] = true;
            $playlists[] = $mapped;
        }

        return Response::json($playlists, 200);
    }

    public function getOwned()
    {
        $query = Playlist::summary()
            ->with('pins', 'tracks', 'tracks.cover')
            ->where('user_id', Auth::user()->id)
            ->orderBy('title', 'asc')
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
                    'normal' => $playlist->getCoverUrl(Image::NORMAL)
                ],
                'is_pinned' => $playlist->hasPinFor(Auth::user()->id),
                'is_public' => $playlist->is_public == 1,
                'track_ids' => $playlist->tracks->pluck('id')
            ];
        }

        return Response::json($playlists, 200);
    }
}
