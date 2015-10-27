<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Peter Deltchev
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
    protected $signature = 'track-cache:rebuild
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
        $this->info('This will run \'php artisan down\' if you proceed and \'php artisan up\' when complete.');

        if ($this->option('force') || $this->confirm('Are you sure you want to delete all to-be-cached files and encode missing non-cached files? [y|N]',
                false)
        ) {

            $this->call('down');

            //==========================================================================================================
            // Delete previously cached tracks
            //==========================================================================================================

            // Find files which are cacheable and NOT master
            $trackFiles = TrackFile::where('is_cacheable', true)
                ->where('is_master', false)
                ->get();

            // Delete above files
            if (count($trackFiles) == 0) {
                $this->info('No tracks found. Continuing.');
            } else {
                $this->info(count($trackFiles) . ' tracks found.');
                $count = 0;

                foreach ($trackFiles as $trackFile) {
                    // Clear expiration so will be re-cached on next request
                    $trackFile->expiration = null;
                    $trackFile->update();

                    // Delete files
                    if (File::exists($trackFile->getFile())) {
                        $count++;
                        File::delete($trackFile->getFile());
                        $this->info('Deleted ' . $trackFile->getFile());
                    }
                }

                $this->info($count . ' files deleted. Deletion complete. Continuing.');
            }

            //==========================================================================================================
            // Update the database entries for cacheable files - non-cacheable to cacheable
            //==========================================================================================================

            $this->info('--- Step 2/4 - Updating is_cacheable entries in database. ---');

            // Find files which are meant to be cacheable and NOT master, but currently not cacheable
            $trackFiles = TrackFile::where('is_cacheable', false)
                ->whereIn('format', array_keys(Track::$CacheableFormats))
                ->where('is_master', false)
                ->get();

            $formats = [];

            // Set above files to cacheable in the database
            foreach ($trackFiles as $trackFile) {
                // Let user know which formats, previously not cached, were made cacheable
                $formats[] = $trackFile->format;

                $trackFile->expiration = null;
                $trackFile->is_cacheable = true;
                $trackFile->update();
            }

            $this->info('Format(s) set from non-cacheable to cacheable: ' . implode(' ', array_unique($formats)));
            $this->info(count($trackFiles) . ' non-cacheable tracks set to cacheable.');

            //==========================================================================================================
            // Update the database entries for cacheable files - cacheable to non-cacheable
            //==========================================================================================================

            // Find files which are NOT meant to be cacheable, but currently cacheable
            $trackFiles = TrackFile::where('is_cacheable', true)
                ->whereNotIn('format', array_keys(Track::$CacheableFormats))
                ->get();

            $formats = [];

            // Set above files to non-cacheable in the database
            foreach ($trackFiles as $trackFile) {
                // Let user know which formats, previously not cached, were made cacheable
                $formats[] = $trackFile->format;

                $trackFile->expiration = null;
                $trackFile->is_cacheable = false;
                $trackFile->update();
            }

            $this->info('Format(s) set from cacheable to non-cacheable: ' . implode(' ', array_unique($formats)));
            $this->info(count($trackFiles) . ' cacheable tracks set to non-cacheable.');

            //==========================================================================================================
            // Delete files which have now been marked as cacheable
            //==========================================================================================================

            $this->info('--- Step 3/4 - Deleting now-cacheable files. ---');

            // Find files which are cacheable and NOT master
            $trackFiles = TrackFile::whereIn('format', array_keys(Track::$CacheableFormats))
                ->where('is_master', false)
                ->get();

            // Delete above files
            if (count($trackFiles) == 0) {
                $this->info('No tracks to delete found. Continuing.');
            } else {
                $count = 0;
                foreach ($trackFiles as $trackFile) {
                    // Delete files if files exist; double-check that they are NOT master files
                    if (File::exists($trackFile->getFile()) && $trackFile->is_master == false) {
                        $count++;
                        File::delete($trackFile->getFile());
                        $this->info('Deleted ' . $trackFile->getFile());
                    }
                }
                $this->info(sprintf('%d files deleted out of %d tracks. Continuing.', $count, count($trackFiles)));
            }

            //==========================================================================================================
            // Encode missing (i.e., now non-cacheable) files
            //==========================================================================================================

            $this->info('--- Step 4/4 - Encoding missing files. ---');

            // Get non-cacheable files
            $trackFiles = TrackFile::where('is_cacheable', false)->get();

            // Record the above files which do not exist (i.e., have not been encoded yet)
            $emptyTrackFiles = [];
            $count = 0;
            foreach ($trackFiles as $trackFile) {
                if (!File::exists($trackFile->getFile())) {
                    $count++;
                    $emptyTrackFiles[] = $trackFile;
                }
            }

            // Encode recorded files
            foreach($emptyTrackFiles as $emptyTrackFile) {
                $this->dispatch(new EncodeTrackFile($emptyTrackFile, false));
            }

            $this->info($count . ' tracks encoded.');

            $this->call('up');
            $this->info('Rebuild complete. Exiting.');

        } else {
            $this->info('Rebuild cancelled. Exiting.');
        }
    }
}
