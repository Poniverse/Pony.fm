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

namespace App\Providers;

use Gate;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\Album;
use App\Models\Genre;
use App\Policies\AlbumPolicy;
use App\Policies\GenrePolicy;
use App\Policies\ShowSongPolicy;
use App\Policies\TrackPolicy;
use App\Models\Track;
use App\Models\User;
use App\Models\ShowSong;
use App\Policies\UserPolicy;

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

        Gate::define('create-announcement', function (User $user) {
            return $user->hasRole('admin');
        });

        $this->registerPolicies();
    }
}
