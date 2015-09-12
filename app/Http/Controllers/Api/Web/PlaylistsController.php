<?php

namespace App\Http\Controllers\Api\Web;

use App\Commands\AddTrackToPlaylistCommand;
use App\Commands\CreatePlaylistCommand;
use App\Commands\DeletePlaylistCommand;
use App\Commands\EditPlaylistCommand;
use App\Http\Controllers\ApiControllerBase;
use App\Image;
use App\Playlist;
use App\ResourceLogItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Response;

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

    public function getIndex()
    {
        $page = 1;
        if (Input::has('page')) {
            $page = Input::get('page');
        }

        $query = Playlist::summary()
            ->with('user', 'user.avatar', 'tracks', 'tracks.cover', 'tracks.user', 'tracks.album', 'tracks.album.user')
            ->userDetails()
            ->orderBy('created_at', 'desc')
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
        $query = Playlist::summary()->with('pins', 'tracks', 'tracks.cover')->where('user_id',
            \Auth::user()->id)->orderBy('title', 'asc')->get();
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
                'is_public' => $playlist->is_public == 1
            ];
        }

        return Response::json($playlists, 200);
    }
}