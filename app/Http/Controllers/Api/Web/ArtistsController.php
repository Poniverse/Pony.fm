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

use Illuminate\Support\Facades\App;
use App\Commands\CreateUserCommand;
use App\Http\Controllers\ApiControllerBase;
use App\Models\Album;
use App\Models\Comment;
use App\Models\Favourite;
use App\Models\Follower;
use App\Models\Image;
use App\Models\Track;
use App\Models\User;
use ColorThief\ColorThief;
use Illuminate\Support\Facades\Gate;
use Helpers;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;

class ArtistsController extends ApiControllerBase
{
    public function getFavourites($slug)
    {
        $user = User::where('slug', $slug)->whereNull('disabled_at')->first();
        if (! $user) {
            abort(404);
        }

        $favs = Favourite::where('user_id', $user->id)
            ->with([
            'track.genre',
            'track.cover',
            'track.user',
            'track.user.avatar',
            'track.album',
            'track.album.cover',
            'track.album.user.avatar',
            'album.cover',
            'album.user',
            'album.user.avatar',
            'track' => function ($query) {
                $query->userDetails();
            },
            'album' => function ($query) {
                $query->userDetails();
            },
            ])->get();

        $tracks = [];
        $albums = [];

        foreach ($favs as $fav) {
            if ($fav->type == \App\Models\Track::class) {
                $tracks[] = Track::mapPublicTrackSummary($fav->track);
            } else {
                if ($fav->type == \App\Models\Album::class) {
                    $albums[] = Album::mapPublicAlbumSummary($fav->album);
                }
            }
        }

        return response()->json([
            'tracks' => $tracks,
            'albums' => $albums,
        ], 200);
    }

    public function getContent($slug)
    {
        $user = User::where('slug', $slug)->whereNull('disabled_at')->first();
        if (! $user) {
            abort(404);
        }

        $query = Track::summary()
            ->published()
            ->listed()
            ->explicitFilter()
            ->with('genre', 'cover', 'user', 'user.avatar', 'album', 'album.cover')
            ->userDetails()
            ->whereUserId($user->id)
            ->whereNotNull('published_at');
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
            ->orderByDesc('created_at')
            ->where('track_count', '>', 0)
            ->whereUserId($user->id);

        $albums = [];

        foreach ($query->get() as $album) {
            $albums[] = Album::mapPublicAlbumSummary($album);
        }

        return response()->json(['singles' => $singles, 'albumTracks' => $tracks, 'albums' => $albums], 200);
    }

    public function getShow($slug)
    {
        $user = User::where('slug', $slug)
            ->whereNull('disabled_at')
            ->userDetails()
            ->with([
                'comments' => function ($query) {
                    $query->with(['user', 'user.avatar']);
                },
            ])
            ->first();
        if (! $user) {
            abort(404);
        }

        $trackQuery = Track::summary()
            ->published()
            ->explicitFilter()
            ->listed()
            ->with('genre', 'cover', 'user', 'album', 'album.cover')
            ->userDetails()
            ->whereUserId($user->id)
            ->whereNotNull('published_at')
            ->orderByDesc('created_at')
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
            'is_following' => false,
        ];

        if ($user->users->count()) {
            $userRow = $user->users[0];
            $userData = [
                'is_following' => (bool) $userRow->is_followed,
            ];
        }

        $palette = ColorThief::getPalette($user->getAvatarUrlLocal(Image::SMALL), 2);
        $formatted_palette = array_map('Helpers::rgb2hex', $palette);

        $followers = Follower::where('artist_id', $user->id)
            ->count();

        return response()->json([
            'artist' => [
                'id' => $user->id,
                'name' => $user->display_name,
                'slug' => $user->slug,
                'is_archived' => (bool) $user->is_archived,
                'avatars' => [
                    'small' => $user->getAvatarUrl(Image::SMALL),
                    'normal' => $user->getAvatarUrl(Image::NORMAL),
                ],
                'avatar_colors' => $formatted_palette,
                'created_at' => $user->created_at,
                'followers' => $followers,
                'following' => [],
                'latest_tracks' => $latestTracks,
                'comments' => $comments,
                'bio' => $user->bio,
                'mlpforums_username' => $user->username,
                'message_url' => $user->message_url,
                'user_data' => $userData,
                'permissions' => [
                    'edit' => Gate::allows('edit', $user),
                ],
                'isAdmin' => $user->hasRole('admin'),
            ],
        ], 200);
    }

    public function getIndex()
    {
        $page = 1;
        if (Request::has('page')) {
            $page = Request::get('page');
        }

        $query = User::where('track_count', '>', 0);
        $count = $query->count();

        // The query results are ordered after they're counted
        // due to Postgres's behaviour when combining those two operations.
        $query->orderBy('display_name');
        $perPage = 40;
        $query->skip(($page - 1) * $perPage)->take($perPage);
        $users = [];

        foreach ($query->get() as $user) {
            $users[] = User::mapPublicUserSummary($user);
        }

        return response()->json(
            ['artists' => $users, 'current_page' => $page, 'total_pages' => ceil($count / $perPage)],
            200
        );
    }

    public function postIndex()
    {
        $name = Request::json('username');

        return $this->execute(new CreateUserCommand($name, $name, null, true));
    }
}
