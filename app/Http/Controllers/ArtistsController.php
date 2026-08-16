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

use App\Models\Album;
use App\Models\Comment;
use App\Models\Favourite;
use App\Models\Follower;
use App\Models\Image;
use App\Models\Track;
use App\Models\User;
use ColorThief\ColorThief;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;
use Inertia\Response;

class ArtistsController extends Controller
{
    const PER_PAGE = 40;

    public function getIndex(Request $request)
    {
        $page = max(1, (int) $request->query('page', 1));

        $query = User::where('track_count', '>', 0);
        $count = $query->count();
        $query->orderBy('display_name')->skip(($page - 1) * self::PER_PAGE)->take(self::PER_PAGE);

        return Inertia::render('artists/index', [
            'artists' => $query->get()->map(fn (User $user) => User::mapPublicUserSummary($user))->all(),
            'currentPage' => $page,
            'totalPages' => (int) ceil($count / self::PER_PAGE),
        ]);
    }

    public function getProfile(Request $request, $slug)
    {
        $user = $this->resolveArtist($slug);
        if ($user instanceof \Symfony\Component\HttpFoundation\Response) {
            return $user;
        }

        $withComments = User::whereSlug($slug)
            ->userDetails()
            ->with(['comments' => fn ($query) => $query->with(['user', 'user.avatar'])])
            ->first();

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

        return $this->renderArtistPage('artists/profile', $user, [
            'latestTracks' => $trackQuery->get()->map(fn (Track $t) => Track::mapPublicTrackSummary($t))->all(),
            'comments' => $withComments->comments->map(fn ($c) => Comment::mapPublic($c))->all(),
        ], $withComments);
    }

    public function getContent(Request $request, $slug)
    {
        $user = $this->resolveArtist($slug);
        if ($user instanceof \Symfony\Component\HttpFoundation\Response) {
            return $user;
        }

        $query = Track::summary()
            ->published()
            ->listed()
            ->explicitFilter()
            ->with('genre', 'cover', 'user', 'user.avatar', 'album', 'album.cover')
            ->userDetails()
            ->whereUserId($user->id)
            ->whereNotNull('published_at');

        $albumTracks = [];
        $singles = [];
        foreach ($query->get() as $track) {
            if ($track->album_id != null) {
                $albumTracks[] = Track::mapPublicTrackSummary($track);
            } else {
                $singles[] = Track::mapPublicTrackSummary($track);
            }
        }

        $albums = Album::summary()
            ->with('user')
            ->orderByDesc('created_at')
            ->where('track_count', '>', 0)
            ->whereUserId($user->id)
            ->get()
            ->map(fn (Album $album) => Album::mapPublicAlbumSummary($album))
            ->all();

        return $this->renderArtistPage('artists/content', $user, [
            'singles' => $singles,
            'albumTracks' => $albumTracks,
            'albums' => $albums,
        ]);
    }

    public function getFavourites(Request $request, $slug)
    {
        $user = $this->resolveArtist($slug);
        if ($user instanceof \Symfony\Component\HttpFoundation\Response) {
            return $user;
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
                'playlist.user',
                'playlist.tracks',
                'playlist.tracks.cover',
                'track' => fn ($query) => $query->userDetails(),
                'album' => fn ($query) => $query->userDetails(),
                'playlist' => fn ($query) => $query->userDetails(),
            ])->get();

        $tracks = [];
        $albums = [];
        $playlists = [];
        foreach ($favs as $fav) {
            if ($fav->type == Track::class && $fav->track) {
                $tracks[] = Track::mapPublicTrackSummary($fav->track);
            } elseif ($fav->type == Album::class && $fav->album) {
                $albums[] = Album::mapPublicAlbumSummary($fav->album);
            } elseif ($fav->type == Playlist::class && $fav->playlist) {
                $playlists[] = Playlist::mapPublicPlaylistSummary($fav->playlist);
            }
        }

        return $this->renderArtistPage('artists/favourites', $user, [
            'tracks' => $tracks,
            'albums' => $albums,
            'playlists' => $playlists,
        ]);
    }

    public function getShortlink($id)
    {
        $user = User::find($id);
        if (! $user || $user->disabled_at !== null) {
            abort(404);
        }

        return Redirect::action([static::class, 'getProfile'], [$user->slug]);
    }

    /** @return User|\Symfony\Component\HttpFoundation\Response */
    private function resolveArtist(string $slug)
    {
        $user = User::whereSlug($slug)->first();

        if (! $user) {
            abort(404);
        }

        if ($user->redirect_to) {
            $newUser = User::find($user->redirect_to);
            if ($newUser) {
                return Redirect::action([static::class, 'getProfile'], [$newUser->slug]);
            }
        }

        if ($user->disabled_at) {
            abort(404);
        }

        return $user;
    }

    private function renderArtistPage(string $component, User $user, array $props, ?User $withUserDetails = null): Response
    {
        $detailed = $withUserDetails ?? User::whereSlug($user->slug)->userDetails()->first();

        $isFollowing = false;
        if ($detailed->users->count()) {
            $isFollowing = (bool) $detailed->users[0]->is_followed;
        }

        // The palette read hits the avatar file on disk; cache it per avatar.
        $palette = Cache::remember(
            'artist-palette-'.$user->id.'-'.($user->avatar_id ?? 'gravatar'),
            86400,
            function () use ($user) {
                try {
                    $palette = ColorThief::getPalette($user->getAvatarUrlLocal(Image::SMALL), 2);

                    return array_map('Helpers::rgb2hex', $palette);
                } catch (\Throwable) {
                    return ['#84528a', '#2e1c31'];
                }
            }
        );

        return Inertia::render($component, [
            ...$props,
            'artist' => [
                'id' => $user->id,
                'name' => $user->display_name,
                'slug' => $user->slug,
                'is_archived' => (bool) $user->is_archived,
                'avatars' => [
                    'small' => $user->getAvatarUrl(Image::SMALL),
                    'normal' => $user->getAvatarUrl(Image::NORMAL),
                ],
                'avatar_colors' => $palette,
                'created_at' => $user->created_at,
                'followers' => Follower::where('artist_id', $user->id)->count(),
                'bio' => $user->bio,
                'mlpforums_username' => $user->username,
                'message_url' => $user->message_url,
                'user_data' => ['is_following' => $isFollowing],
                'permissions' => ['edit' => Gate::allows('edit', $user)],
                'isAdmin' => $user->hasRole('admin'),
            ],
        ])->withViewData(['meta' => View::make('meta.artist', ['artist' => $user])->render()]);
    }
}
