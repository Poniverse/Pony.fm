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

namespace Poniverse\Ponyfm\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Poniverse\Ponyfm\Models\Album;
use Poniverse\Ponyfm\Models\Genre;
use Poniverse\Ponyfm\Policies\AlbumPolicy;
use Poniverse\Ponyfm\Policies\GenrePolicy;
use Poniverse\Ponyfm\Policies\ShowSongPolicy;
use Poniverse\Ponyfm\Policies\TrackPolicy;
use Poniverse\Ponyfm\Models\Track;
use Poniverse\Ponyfm\Models\User;
use Poniverse\Ponyfm\Models\ShowSong;
use Poniverse\Ponyfm\Policies\UserPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Genre::class => GenrePolicy::class,
        Track::class => TrackPolicy::class,
        Album::class => AlbumPolicy::class,
        User::class => UserPolicy::class,
        ShowSong::class => ShowSongPolicy::class
    ];

    /**
     * Register any application authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        Gate::define('access-admin-area', function (User $user) {
            return $user->hasRole('admin');
        });

        Gate::define('create-genre', function (User $user) {
            return $user->hasRole('admin');
        });

        Gate::define('create-show-song', function (User $user) {
            return $user->hasRole('admin');
        });

        Gate::define('create-user', function (User $user) {
            return $user->hasRole('admin');
        });

        $this->registerPolicies();
    }
}
