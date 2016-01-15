<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Peter Deltchev
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

namespace Poniverse\Ponyfm\Library;

use DB;
use Elasticsearch\Client;
use Illuminate\Database\Eloquent\Collection;
use Poniverse\Ponyfm\Models\Album;
use Poniverse\Ponyfm\Models\Playlist;
use Poniverse\Ponyfm\Models\Track;
use Poniverse\Ponyfm\Models\User;

class Search {
    protected $elasticsearch;
    protected $index;

    public function __construct(Client $connection, string $indexName) {
        $this->elasticsearch = $connection;
        $this->index = $indexName;
    }

    /**
     * @param string $query
     * @param int $resultsPerContentType
     * @return array
     */
    public function searchAllContent(string $query, int $resultsPerContentType = 10) {
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
                                'title',
                                'artist',
                                'genre',
                                'track_type',
                                'show_songs',
                            ],
                        ],
                    ],
                    'size' => $resultsPerContentType
                ],

                //===== Albums =====//
                ['type' => 'album'],
                [
                    'query' => [
                        'multi_match' => [
                            'query' => $query,
                            'fields' => [
                                'title',
                                'artist',
                                'tracks',
                            ],
                        ],
                    ],
                    'size' => $resultsPerContentType
                ],

                //===== Playlists =====//
                ['type' => 'playlist'],
                [
                    'query' => [
                        'multi_match' => [
                            'query' => $query,
                            'fields' => [
                                'title',
                                'user',
                            ],
                        ],
                    ],
                    'size' => $resultsPerContentType
                ],

                //===== Users =====//
                ['type' => 'user'],
                [
                    'query' => [
                        'multi_match' => [
                            'query' => $query,
                            'fields' => [
                                'display_name',
                            ],
                        ],
                    ],
                    'size' => $resultsPerContentType
                ],
            ]
        ]);

        $tracks = $this->transformToEloquent(Track::class, $results['responses'][0]['hits']['hits']);
        $albums = $this->transformToEloquent(Album::class, $results['responses'][1]['hits']['hits']);
        $playlists = $this->transformToEloquent(Playlist::class, $results['responses'][2]['hits']['hits']);
        $users = $this->transformToEloquent(User::class, $results['responses'][3]['hits']['hits']);

        return [
            'tracks'    => $tracks,
            'albums'    => $albums,
            'playlists' => $playlists,
            'users'     => $users
        ];
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
    protected function transformToEloquent(string $modelClass, array $searchHits) {
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

        $modelInstances = $modelClass::withTrashed()
            ->whereIn('id', array_keys($ids))
            ->orderBy(DB::raw($caseStatement))
            ->get();

        return $modelInstances;
    }
}