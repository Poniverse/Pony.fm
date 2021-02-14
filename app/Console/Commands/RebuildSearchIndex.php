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

        $trackProgress = $this->output->createProgressBar($totalTracks);
        $this->info('Processing tracks...');
        Track::withTrashed()->chunk(200, function (Collection $tracks) use ($trackProgress) {
            foreach ($tracks as $track) {
                /** @var Track $track */
                $trackProgress->advance();
                $track->updateElasticsearchEntry();
            }
        });
        $trackProgress->finish();
        $this->line('');

        $albumProgress = $this->output->createProgressBar($totalAlbums);
        $this->info('Processing albums...');
        Album::withTrashed()->chunk(200, function (Collection $albums) use ($albumProgress) {
            foreach ($albums as $album) {
                /** @var Album $album */
                $albumProgress->advance();
                $album->updateElasticsearchEntry();
            }
        });
        $albumProgress->finish();
        $this->line('');

        $playlistProgress = $this->output->createProgressBar($totalPlaylists);
        $this->info('Processing playlists...');
        Playlist::withTrashed()->chunk(200, function (Collection $playlists) use ($playlistProgress) {
            foreach ($playlists as $playlist) {
                /** @var Playlist $playlist */
                $playlistProgress->advance();
                $playlist->updateElasticsearchEntry();
            }
        });
        $playlistProgress->finish();
        $this->line('');

        $userProgress = $this->output->createProgressBar($totalUsers);
        $this->info('Processing users...');
        User::chunk(200, function (Collection $users) use ($userProgress) {
            foreach ($users as $user) {
                /** @var User $user */
                $userProgress->advance();
                $user->updateElasticsearchEntry();
            }
        });
        $userProgress->finish();
        $this->line('');
        $this->info('Everything has been queued for re-indexing!');
    }
}
