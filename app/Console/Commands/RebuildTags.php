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

use App\Models\Track;
use Illuminate\Console\Command;

class RebuildTags extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rebuild:tags
                            {trackId? : ID of the track to rebuild tags for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rewrites tags in track files, ensuring they\'re up to date.';

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
        if ($this->argument('trackId')) {
            $track = Track::findOrFail($this->argument('trackId'));
            $tracks = [$track];
        } else {
            $tracks = Track::whereNotNull('published_at')->withTrashed()->orderBy('id', 'asc')->get();
        }

        $numberOfTracks = sizeof($tracks);

        $this->info("Updating tags for ${numberOfTracks} tracks...");
        $bar = $this->output->createProgressBar($numberOfTracks);

        foreach ($tracks as $track) {
            /** @var $track Track */
            $track->updateTags();
            $bar->advance();
        }

        $bar->finish();
        $this->line('');
    }
}
