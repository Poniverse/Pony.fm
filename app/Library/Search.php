<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Feld0.
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

namespace App\Library;

use App\Models\Album;
use App\Models\Playlist;
use App\Models\Track;
use App\Models\User;
use Elasticsearch\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class Search
{
    protected $elasticsearch;
    protected $index;

    public function __construct(Client $connection, string $indexName)
    {
        $this->elasticsearch = $connection;
        $this->index = $indexName;
    }

    /**
     * @param string $query
     * @param int $resultsPerContentType
     * @return array
     */
    public function searchAllContent(string $query)
    {
        $results = $this->elasticsearch->msearch([
            'index' => $this->index,
            'body' => [
                //===== Tracks=====//
                ['type' => 'track'],
                [
                    'query' => [
                        'multi_match' => [
                            'query' => $query,
                            'fields' => [
                                'title^3',
                                'artist^2',
                                'genre',
                                'track_type',
                                'show_songs^2',
                            ],
                            'tie_breaker' => 0.3,
                        ],
                    ],
                    'size' => 13,
                ],

                //===== Albums =====//
                ['type' => 'album'],
                [
                    'query' => [
                        'multi_match' => [
                            'query' => $query,
                            'fields' => [
                                'title^2',
                                'artist',
                                'tracks',
                            ],
                            'tie_breaker' => 0.3,
                        ],
                    ],
                    'size' => 3,
                ],

                //===== Playlists =====//
                ['type' => 'playlist'],
                [
                    'query' => [
                        'multi_match' => [
                            'query' => $query,
                            'fields' => [
                                'title^3',
                                'curator',
                                'tracks^2',
                            ],
                            'tie_breaker' => 0.3,
                        ],
                    ],
                    'size' => 3,
                ],

                //===== Users =====//
                ['type' => 'user'],
                [
                    'query' => [
                        'multi_match' => [
                            'query' => $query,
                            'fields' => [
                                'display_name',
                                'tracks',
                            ],
                            'tie_breaker' => 0.3,
                        ],
                    ],
                    'size' => 3,
                ],
            ],
        ]);

        $tracks = $this->transformTracks($results['responses'][0]['hits']['hits']);
        $albums = $this->transformAlbums($results['responses'][1]['hits']['hits']);
        $playlists = $this->transformPlaylist($results['responses'][2]['hits']['hits']);
        $users = $this->transformUsers($results['responses'][3]['hits']['hits']);

        return [
            'tracks'    => $tracks,
            'albums'    => $albums,
            'playlists' => $playlists,
            'users'     => $users,
        ];
    }

    protected function transformTracks(array $searchHits)
    {
        $tracks = $this->transformToEloquent(Track::class, $searchHits);
        $tracks = $tracks->map(function (Track $track) {
            return Track::mapPublicTrackSummary($track);
        });

        return $tracks;
    }

    protected function transformAlbums(array $searchHits)
    {
        $albums = $this->transformToEloquent(Album::class, $searchHits);
        $albums = $albums->map(function (Album $album) {
            return Album::mapPublicAlbumSummary($album);
        });

        return $albums;
    }

    protected function transformPlaylist(array $searchHits)
    {
        $playlists = $this->transformToEloquent(Playlist::class, $searchHits);
        $playlists = $playlists->map(function (Playlist $playlist) {
            return Playlist::mapPublicPlaylistSummary($playlist);
        });

        return $playlists;
    }

    protected function transformUsers(array $searchHits)
    {
        $users = $this->transformToEloquent(User::class, $searchHits);
        $users = $users->map(function (User $user) {
            return User::mapPublicUserSummary($user);
        });

        return $users;
    }

    /**
     * Transforms the given Elasticsearch results into a collection of corresponding
     * Eloquent models.
     *
     * This method assumes that the given class uses soft deletes.
     *
     * @param string $modelClass The Eloquent model class to instantiate these results as
     * @param array $searchHits
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function transformToEloquent(string $modelClass, array $searchHits)
    {
        if (empty($searchHits)) {
            return new Collection();
        }

        $ids = [];
        $caseStatement = 'CASE id ';

        $i = 0;
        foreach ($searchHits as $result) {
            $ids[$result['_id']] = $result['_score'];
            $caseStatement .= "WHEN ${result['_id']} THEN $i ";
            $i++;
        }
        $caseStatement .= 'END';

        /** @var Builder $modelInstances */
        $modelInstances = $modelClass::query();

        if (method_exists($modelClass, 'withTrashed')) {
            $modelInstances = $modelInstances->withTrashed();
        }

        $modelInstances = $modelInstances
            ->whereIn('id', array_keys($ids))
            ->orderBy(DB::raw($caseStatement))
            ->get();

        return $modelInstances;
    }
}
