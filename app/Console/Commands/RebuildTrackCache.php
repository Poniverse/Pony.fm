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
use Illuminate\Foundation\Bus\DispatchesJobs;
use Poniverse\Ponyfm\Jobs\EncodeTrackFile;
use Poniverse\Ponyfm\Track;
use Poniverse\Ponyfm\TrackFile;

class RebuildTrackCache extends Command
{

    use DispatchesJobs;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rebuild:track-cache
                            {--force : Skip all prompts.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuilds the track cache for when $CacheableFormats is changed. Deletes cacheable files and encodes missing files which are not cacheable.';

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
        $this->info('***');
        $this->info('If this is your first time running this command, it is *highly* recommended that you ensure the file sizes for all track files have been populated.');
        $this->info('***');

        if ($this->option('force') || $this->confirm('Are you sure you want to delete all to-be-cached track files and encode missing non-cached track files?',
                false)
        ) {

            //==========================================================================================================
            // Delete previously cached track files
            //==========================================================================================================

            $this->output->newLine(1);
            $this->info('========== Step 1/4 - Deleting previously cached track files. ==========');

            $count = 0;

            // Chunk track files which are cacheable and NOT master
            TrackFile::where('is_cacheable', true)
                ->where('is_master', false)
                ->chunk(200, function ($trackFiles) use (&$count) {
                    // Delete chunked track files
                    foreach ($trackFiles as $trackFile) {
                        // Clear expiration so will be re-cached on next request
                        $trackFile->expires_at = null;
                        $trackFile->update();

                        // Delete files
                        if (File::exists($trackFile->getFile())) {
                            $count++;
                            File::delete($trackFile->getFile());
                            $this->info('Deleted ' . $trackFile->getFile());
                        }
                    }

                    $this->info($count . ' track files deleted. Deletion complete. Continuing.');
                });

            //==========================================================================================================
            // Update the database entries for cacheable track files - non-cacheable to cacheable
            //==========================================================================================================

            $this->output->newLine(3);
            $this->info('========== Step 2/4 - Updating is_cacheable entries in database. ==========');

            $trackFileCount = 0;
            $formats = [];

            // Find track files which are meant to be cacheable and NOT master, but currently not cacheable
            TrackFile::where('is_cacheable', false)
                ->whereIn('format', Track::$CacheableFormats)
                ->where('is_master', false)
                ->chunk(200, function ($trackFiles) use (&$trackFileCount, &$formats) {
                    $this->output->newLine(1);
                    $this->info('---------- Start Chunk ----------');

                    // Set above files to cacheable in the database
                    foreach ($trackFiles as $trackFile) {
                        $trackFileCount++;

                        // Let user know which formats, previously not cached, were made cacheable
                        $formats[] = $trackFile->format;

                        $trackFile->expires_at = null;
                        $trackFile->is_cacheable = true;
                        $trackFile->update();
                    }

                    $this->info('----------- End Chunk -----------');
                    $this->output->newLine(1);
                });

            $this->info('Format(s) set from non-cacheable to cacheable: ' . implode(' ', array_unique($formats)));
            $this->info($trackFileCount . ' non-cacheable track files set to cacheable.');

            $this->output->newLine(2);

            //==========================================================================================================
            // Update the database entries for cacheable track files - cacheable to non-cacheable
            //==========================================================================================================

            $trackFileCount = 0;
            $formats = [];

            // Chunk track files which are NOT meant to be cacheable, but currently cacheable
            TrackFile::where('is_cacheable', true)
                ->whereNotIn('format', Track::$CacheableFormats)
                ->chunk(200, function ($trackFiles) use (&$trackFileCount, &$formats) {
                    $this->output->newLine(1);
                    $this->info('---------- Start Chunk ----------');

                    // Set chunked track files to non-cacheable in the database
                    foreach ($trackFiles as $trackFile) {
                        $trackFileCount++;

                        // Let user know which formats, previously not cached, were made cacheable
                        $formats[] = $trackFile->format;

                        $trackFile->expires_at = null;
                        $trackFile->is_cacheable = false;
                        $trackFile->update();
                    }

                    $this->info('----------- End Chunk -----------');
                    $this->output->newLine(1);
                    $this->output->newLine(1);
                });


            $this->info('Format(s) set from cacheable to non-cacheable: ' . implode(' ', array_unique($formats)));
            $this->info($trackFileCount . ' cacheable track files set to non-cacheable.');

            //==========================================================================================================
            // Delete track files which have now been marked as cacheable
            //==========================================================================================================

            $this->output->newLine(3);
            $this->info('========== Step 3/4 - Deleting now-cacheable track files. ==========');

            $count = 0;
            $trackFileCount = 0;

            // Find track files which are cacheable and NOT master
            TrackFile::whereIn('format', Track::$CacheableFormats)
                ->where('is_master', false)
                ->chunk(200, function ($trackFiles) use (&$count, &$trackFileCount) {
                    $this->output->newLine(1);
                    $this->info('---------- Start Chunk ----------');

                    foreach ($trackFiles as $trackFile) {
                        $trackFileCount++;

                        // Delete track files if track files exist; double-check that they are NOT master files
                        if (File::exists($trackFile->getFile()) && $trackFile->is_master == false) {
                            $count++;

                            File::delete($trackFile->getFile());
                            $this->info('Deleted ' . $trackFile->getFile());
                        }
                    }

                    $this->info('----------- End Chunk -----------');
                    $this->output->newLine(1);
                });


            $this->info(sprintf('%d track files deleted out of %d track files. Continuing.', $count, $trackFileCount));

            //==========================================================================================================
            // Encode missing (i.e., now non-cacheable) track files
            //==========================================================================================================

            $this->output->newLine(3);
            $this->info('========== Step 4/4 - Encoding missing track files. ==========');

            $count = 0;

            // Chunk non-cacheable track files
            TrackFile::where('is_cacheable', false)
                ->where('is_master', false)
                ->chunk(200, function ($trackFiles) use (&$count) {
                $this->output->newLine(1);
                $this->info('---------- Start Chunk ----------');

                // Record the track files which do not exist (i.e., have not been encoded yet)
                $emptyTrackFiles = [];

                foreach ($trackFiles as $trackFile) {
                    if (!File::exists($trackFile->getFile())) {
                        $count++;
                        $emptyTrackFiles[] = $trackFile;
                    }
                }

                // Encode recorded track files
                foreach ($emptyTrackFiles as $emptyTrackFile) {
                    $this->info("Started encoding track file ID {$emptyTrackFile->id}");
                    $this->dispatch(new EncodeTrackFile($emptyTrackFile, false));
                }

                $this->info('----------- End Chunk -----------');
                $this->output->newLine(1);
            });


            $this->info($count . ' track files encoded.');
            $this->output->newLine(1);

            $this->info('Rebuild complete. Exiting.');

        } else {
            $this->info('Rebuild cancelled. Exiting.');
        }
    }
}
