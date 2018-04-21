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

namespace Poniverse\Ponyfm\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use PfmValidator;
use Poniverse;
use Validator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Validator::resolver(function ($translator, $data, $rules, $messages) {
            return new PfmValidator($translator, $data, $rules, $messages);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Poniverse::class, function (Application $app) {
            return new Poniverse($app['config']->get('poniverse.client_id'), $app['config']->get('poniverse.secret'));
        });

        $this->app->bind(Poniverse\Ponyfm\Library\Search::class, function (Application $app) {
            return new Poniverse\Ponyfm\Library\Search(
                \Elasticsearch::connection(),
                $app['config']->get('ponyfm.elasticsearch_index')
            );
        });

        // NOTE: Use integer keys exclusively for Pony.fm's morphMap to avoid
        //       any weirdness with merging array indices. $merge = false is
        //       set below so that no morphMap array merging happens!
        Relation::morphMap([
            Poniverse\Ponyfm\Models\Activity::TARGET_TRACK => Poniverse\Ponyfm\Models\Track::class,
            Poniverse\Ponyfm\Models\Activity::TARGET_ALBUM => Poniverse\Ponyfm\Models\Album::class,
            Poniverse\Ponyfm\Models\Activity::TARGET_PLAYLIST => Poniverse\Ponyfm\Models\Playlist::class,
            Poniverse\Ponyfm\Models\Activity::TARGET_USER => Poniverse\Ponyfm\Models\User::class,
            Poniverse\Ponyfm\Models\Activity::TARGET_COMMENT => Poniverse\Ponyfm\Models\Comment::class,
        ], false);
    }
}
