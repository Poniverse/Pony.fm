<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Kelvin Zhang
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

use File;
use Illuminate\Console\Command;
use Poniverse\Ponyfm\Models\TrackFile;

class RebuildFilesizes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rebuild:filesizes
                            {--force : Skip all prompts.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuilds the filesize cache for each track file which currently exists on disk.';

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
        $this->info('This will only rebuild the cache for track files which exist on disk; non-existent files will be skipped.');

        if ($this->option('force') || $this->confirm('Are you sure you want to rebuild the filesize cache? [y|N]',
                false)
        ) {

            TrackFile::chunk(200, function($trackFiles) {

                $this->info('========== Start Chunk ==========');

                foreach ($trackFiles as $trackFile) {
                    /** @var TrackFile $trackFile */

                    if (File::exists($trackFile->getFile())) {
                        $size = $trackFile->updateFilesize();
                        $this->info('ID '.$trackFile->id.' processed - '.$size.' bytes');
                    } else {
                        $this->info('ID '.$trackFile->id.' skipped');
                    }
                }

                $this->info('=========== End Chunk ===========');

            });

            $this->info('Rebuild complete. Exiting.');

        } else {
            $this->info('Rebuild cancelled. Exiting.');
        }
    }
}
