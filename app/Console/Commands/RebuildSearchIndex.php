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

namespace Poniverse\Ponyfm\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Poniverse\Ponyfm\Models\Album;
use Poniverse\Ponyfm\Models\Playlist;
use Poniverse\Ponyfm\Models\Track;
use Poniverse\Ponyfm\Models\User;

class RebuildSearchIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rebuild:search';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuilds the Elasticsearch index.';

    /**
     * Create a new command instance.
     *
     * @return void
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
        $totalTracks = Track::withTrashed()->count();
        $totalAlbums = Album::withTrashed()->count();
        $totalPlaylists = Playlist::withTrashed()->count();
        $totalUsers = User::count();

        Track::withTrashed()->chunk(200, function(Collection $tracks) {
            foreach($tracks as $track) {
                $this->info("Processing track #{$track->id}...");
                $track->ensureElasticsearchEntryIsUpToDate();
            }
        });

        Album::withTrashed()->chunk(200, function(Collection $albums) {
            foreach($albums as $album) {
                $this->info("Processing album #{$album->id}...");
                $album->ensureElasticsearchEntryIsUpToDate();
            }
        });

//        Playlist::withTrashed()->chunk(200, function(Collection $playlists) {
//            foreach($playlists as $playlist) {
//                $this->info("Processing playlist #{$playlist->id}...");
//                $playlist->ensureElasticsearchEntryIsUpToDate();
//            }
//        });
//
//        User::withTrashed()->chunk(200, function(User $user) {
//            $user->ensureElasticsearchEntryIsUpToDate();
//        });
    }
}
