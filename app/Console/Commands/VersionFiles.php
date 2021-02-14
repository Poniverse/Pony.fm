<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Kelvin Zhang.
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

use App\Models\TrackFile;
use Illuminate\Support\Facades\File;
use Illuminate\Console\Command;

class VersionFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'version-files
                            {--force : Skip all prompts.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Replaces track files of format [name].[ext] with [name]-v[version].[ext]';

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
        $this->info('This will only version track files which exist on disk; non-existent files will be skipped.');

        if ($this->option('force') || $this->confirm('Are you sure you want to rename all unversioned track files? [y|N]', false)) {
            TrackFile::chunk(200, function ($trackFiles) {
                $this->info('========== Start Chunk ==========');

                foreach ($trackFiles as $trackFile) {
                    /** @var TrackFile $trackFile */

                    // Check whether the unversioned file exists
                    if (! File::exists($trackFile->getUnversionedFile())) {
                        $this->info('ID '.$trackFile->id.' skipped - file not found');
                        continue;
                    }

                    // Version the file and check the outcome
                    if (File::move($trackFile->getUnversionedFile(), $trackFile->getFile())) {
                        $this->info('ID '.$trackFile->id.' processed');
                    } else {
                        $this->error('ID '.$trackFile->id.' was unable to be renamed');
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
