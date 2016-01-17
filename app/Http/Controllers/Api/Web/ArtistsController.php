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

use Poniverse\Ponyfm\Models\Album;
use Poniverse\Ponyfm\Models\Comment;
use Poniverse\Ponyfm\Models\Favourite;
use Poniverse\Ponyfm\Http\Controllers\ApiControllerBase;
use Poniverse\Ponyfm\Models\Image;
use Poniverse\Ponyfm\Models\Track;
use Poniverse\Ponyfm\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Response;

class ArtistsController extends ApiControllerBase
{
    public function getFavourites($slug)
    {
        $user = User::whereSlug($slug)->first();
        if (!$user) {
            App::abort(404);
        }

        $favs = Favourite::whereUserId($user->id)->with([
            'track.genre',
            'track.cover',
            'track.user',
            'album.cover',
            'album.user',
            'track' => function ($query) {
                $query->userDetails();
            },
            'album' => function ($query) {
                $query->userDetails();
            }
        ])->get();

        $tracks = [];
        $albums = [];

        foreach ($favs as $fav) {
            if ($fav->type == 'Poniverse\Ponyfm\Models\Track') {
                $tracks[] = Track::mapPublicTrackSummary($fav->track);
            } else {
                if ($fav->type == 'Poniverse\Ponyfm\Models\Album') {
                    $albums[] = Album::mapPublicAlbumSummary($fav->album);
                }
            }
        }

        return Response::json([
            'tracks' => $tracks,
            'albums' => $albums
        ], 200);
    }

    public function getContent($slug)
    {
        $user = User::whereSlug($slug)->first();
        if (!$user) {
            App::abort(404);
        }

        $query = Track::summary()->published()->listed()->explicitFilter()->with('genre', 'cover',
            'user')->userDetails()->whereUserId($user->id)->whereNotNull('published_at');
        $tracks = [];
        $singles = [];

        foreach ($query->get() as $track) {
            if ($track->album_id != null) {
                $tracks[] = Track::mapPublicTrackSummary($track);
            } else {
                $singles[] = Track::mapPublicTrackSummary($track);
            }
        }

        $query = Album::summary()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->where('track_count', '>', 0)
            ->whereUserId($user->id);

        $albums = [];

        foreach ($query->get() as $album) {
            $albums[] = Album::mapPublicAlbumSummary($album);
        }

        return Response::json(['singles' => $singles, 'albumTracks' => $tracks, 'albums' => $albums], 200);
    }

    public function getShow($slug)
    {
        $user = User::whereSlug($slug)
            ->whereNull('disabled_at')
            ->userDetails()
            ->with([
                'comments' => function ($query) {
                    $query->with('user');
                }
            ])
            ->first();
        if (!$user) {
            App::abort(404);
        }

        $trackQuery = Track::summary()
            ->published()
            ->explicitFilter()
            ->listed()
            ->with('genre', 'cover', 'user')
            ->userDetails()
            ->whereUserId($user->id)
            ->whereNotNull('published_at')
            ->orderBy('created_at', 'desc')
            ->take(20);

        $latestTracks = [];
        foreach ($trackQuery->get() as $track) {
            $latestTracks[] = Track::mapPublicTrackSummary($track);
        }

        $comments = [];
        foreach ($user->comments as $comment) {
            $comments[] = Comment::mapPublic($comment);
        }

        $userData = [
            'is_following' => false
        ];

        if ($user->users->count()) {
            $userRow = $user->users[0];
            $userData = [
                'is_following' => (bool)$userRow->is_followed
            ];
        }

        return Response::json([
            'artist' => [
                'id' => (int)$user->id,
                'name' => $user->display_name,
                'slug' => $user->slug,
                'is_archived' => (bool)$user->is_archived,
                'avatars' => [
                    'small' => $user->getAvatarUrl(Image::SMALL),
                    'normal' => $user->getAvatarUrl(Image::NORMAL)
                ],
                'created_at' => $user->created_at,
                'followers' => [],
                'following' => [],
                'latest_tracks' => $latestTracks,
                'comments' => $comments,
                'bio' => $user->bio,
                'mlpforums_username' => $user->username,
                'message_url' => $user->message_url,
                'user_data' => $userData
            ]
        ], 200);
    }

    public function getIndex()
    {
        $page = 1;
        if (Input::has('page')) {
            $page = Input::get('page');
        }

        $query = User::orderBy('display_name', 'asc')
            ->where('track_count', '>', 0);

        $count = $query->count();
        $perPage = 40;

        $query->skip(($page - 1) * $perPage)->take($perPage);
        $users = [];

        foreach ($query->get() as $user) {
            $users[] = User::mapPublicUserSummary($user);
        }

        return Response::json(["artists" => $users, "current_page" => $page, "total_pages" => ceil($count / $perPage)],
            200);
    }
}
