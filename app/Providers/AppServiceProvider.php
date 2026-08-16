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

namespace App\Providers;

use App\Library\Search;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use PfmValidator;

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
        $this->app->singleton(Client::class, function (Application $app) {
            $connection = $app['config']->get('elasticsearch.defaultConnection', 'default');
            $config = $app['config']->get("elasticsearch.connections.{$connection}");

            $hosts = array_map(function (array $host) {
                return array_filter(
                    array_intersect_key($host, array_flip(['host', 'port', 'scheme', 'path', 'user', 'pass'])),
                    fn ($value) => $value !== null
                );
            }, $config['hosts']);

            $builder = ClientBuilder::create()->setHosts($hosts);

            if (!empty($config['retries'])) {
                $builder->setRetries($config['retries']);
            }

            return $builder->build();
        });

        $this->app->bind(Search::class, function (Application $app) {
            return new Search(
                $app->make(Client::class),
                $app['config']->get('ponyfm.elasticsearch_index')
            );
        });

        // NOTE: Use integer keys exclusively for Pony.fm's morphMap to avoid
        //       any weirdness with merging array indices. $merge = false is
        //       set below so that no morphMap array merging happens!
        Relation::morphMap([
            \App\Models\Activity::TARGET_TRACK => \App\Models\Track::class,
            \App\Models\Activity::TARGET_ALBUM => \App\Models\Album::class,
            \App\Models\Activity::TARGET_PLAYLIST => \App\Models\Playlist::class,
            \App\Models\Activity::TARGET_USER => \App\Models\User::class,
            \App\Models\Activity::TARGET_COMMENT => \App\Models\Comment::class,
        ], false);
    }
}
