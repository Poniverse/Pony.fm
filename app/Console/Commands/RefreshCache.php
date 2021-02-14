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

namespace App\Console\Commands;

use App\Models\ResourceLogItem;
use DB;
use Illuminate\Console\Command;

class RefreshCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refresh-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refreshes cache tables for views and downloads';

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        DB::connection()->disableQueryLog();

        DB::table('tracks')->update(['comment_count' => DB::raw('(SELECT COUNT(id) FROM comments WHERE comments.track_id = tracks.id AND deleted_at IS NULL)')]);

        DB::table('albums')->update([
            'comment_count' => DB::raw('(SELECT COUNT(id) FROM comments WHERE comments.album_id = albums.id AND deleted_at IS NULL)'),
            'track_count' => DB::raw('(SELECT COUNT(id) FROM tracks WHERE album_id = albums.id)')
        ]);

        DB::table('playlists')->update([
            'comment_count' => DB::raw('(SELECT COUNT(id) FROM comments WHERE comments.playlist_id = playlists.id AND deleted_at IS NULL)'),
            'track_count' => DB::raw('(SELECT COUNT(id) FROM playlist_track WHERE playlist_id = playlists.id)')
        ]);

        DB::table('users')->update([
            'comment_count' => DB::raw('(SELECT COUNT(id) FROM comments WHERE comments.profile_id = users.id AND deleted_at IS NULL)'),
            'track_count' => DB::raw('(SELECT COUNT(id) FROM tracks WHERE deleted_at IS NULL AND published_at IS NOT NULL AND user_id = users.id)')
        ]);

        $users = DB::table('users')->get();
        $cacheItems = [];
        $resources = [
            'album' => [],
            'playlist' => [],
            'track' => []
        ];

        foreach ($users as $user) {
            $cacheItems[$user->id] = [
                'album' => [],
                'playlist' => [],
                'track' => [],
            ];
        }

        $logItems = DB::table('resource_log_items')->get();
        foreach ($logItems as $item) {
            $type = '';
            $id = 0;

            if ($item->album_id) {
                $type = 'album';
                $id = $item->album_id;
            } else {
                if ($item->playlist_id) {
                    $type = 'playlist';
                    $id = $item->playlist_id;
                } else {
                    if ($item->track_id) {
                        $type = 'track';
                        $id = $item->track_id;
                    }
                }
            }

            $resource = $this->getCacheItem($resources, $type, $id);

            if ($item->user_id != null) {
                $userResource = $this->getUserCacheItem($cacheItems, $item->user_id, $type, $id);

                if ($item->log_type == ResourceLogItem::DOWNLOAD) {
                    $userResource['download_count']++;
                } else {
                    if ($item->log_type == ResourceLogItem::VIEW) {
                        $userResource['view_count']++;
                    } else {
                        if ($item->log_type == ResourceLogItem::PLAY) {
                            $userResource['play_count']++;
                        }
                    }
                }

                $cacheItems[$item->user_id][$type][$id] = $userResource;
            }

            if ($item->log_type == ResourceLogItem::DOWNLOAD) {
                $resource['download_count']++;
            } else {
                if ($item->log_type == ResourceLogItem::VIEW) {
                    $resource['view_count']++;
                } else {
                    if ($item->log_type == ResourceLogItem::PLAY) {
                        $resource['play_count']++;
                    }
                }
            }

            $resources[$type][$id] = $resource;
        }

        $pins = DB::table('pinned_playlists')->get();
        foreach ($pins as $pin) {
            $userResource = $this->getUserCacheItem($cacheItems, $pin->user_id, 'playlist', $pin->playlist_id);
            $userResource['is_pinned'] = true;
            $cacheItems[$pin->user_id]['playlist'][$pin->playlist_id] = $userResource;
        }

        $favs = DB::table('favourites')->get();
        foreach ($favs as $fav) {
            $type = '';
            $id = 0;

            if ($fav->album_id) {
                $type = 'album';
                $id = $fav->album_id;
            } else {
                if ($fav->playlist_id) {
                    $type = 'playlist';
                    $id = $fav->playlist_id;
                } else {
                    if ($fav->track_id) {
                        $type = 'track';
                        $id = $fav->track_id;
                    }
                }
            }

            $userResource = $this->getUserCacheItem($cacheItems, $fav->user_id, $type, $id);
            $userResource['is_favourited'] = true;
            $cacheItems[$fav->user_id][$type][$id] = $userResource;

            $resource = $this->getCacheItem($resources, $type, $id);
            $resource['favourite_count']++;
            $resources[$type][$id] = $resource;
        }

        foreach (DB::table('followers')->get() as $follower) {
            $userResource = $this->getUserCacheItem($cacheItems, $follower->user_id, 'artist', $follower->artist_id);
            $userResource['is_followed'] = true;
            $cacheItems[$follower->user_id]['artist'][$follower->artist_id] = $userResource;
        }

        foreach ($resources as $name => $resourceArray) {
            foreach ($resourceArray as $id => $resource) {
                DB::table($name.'s')->whereId($id)->update($resource);
            }
        }

        DB::table('resource_users')->delete();
        foreach ($cacheItems as $cacheItem) {
            foreach ($cacheItem as $resources) {
                foreach ($resources as $resource) {
                    DB::table('resource_users')->insert($resource);
                }
            }
        }
    }

    private function getCacheItem(&$resources, $type, $id)
    {
        if (!isset($resources[$type][$id])) {
            $item = [
                'view_count' => 0,
                'download_count' => 0,
                'favourite_count' => 0,
            ];

            if ($type == 'track') {
                $item['play_count'] = 0;
            }

            $resources[$type][$id] = $item;

            return $item;
        }

        return $resources[$type][$id];
    }

    private function getUserCacheItem(&$items, $userId, $type, $id)
    {
        if (!isset($items[$userId][$type][$id])) {
            $item = [
                'is_followed' => false,
                'is_favourited' => false,
                'is_pinned' => false,
                'view_count' => 0,
                'play_count' => 0,
                'download_count' => 0,
                'user_id' => $userId
            ];

            $item[$type.'_id'] = $id;

            $items[$userId][$type][$id] = $item;

            return $item;
        }

        return $items[$userId][$type][$id];
    }
}
