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

use Carbon\Carbon;
use File;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Poniverse\Ponyfm\TrackFile;

class ClearTrackCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'track-cache:clear
                            {--tracks=expired : Clear only [expired] (default) or [all] cached tracks.}
                            {--force : Skip all prompts.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears cached tracks. Defaults to expired tracks. Usage: php artisan track-cache:clear [--tracks=expired|all]';

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
        if ($this->option('tracks') == 'all') {
            // Get all cacheable tracks
            $trackFiles = TrackFile::where('is_cacheable', true)->get();
        } else {
            // Get all expired tracks
            $trackFiles = TrackFile::where('is_cacheable', true)
                ->where('expiration', '<=', Carbon::now())
                ->get();

        }

        // Delete above tracks
        if (count($trackFiles) === 0) {
            $this->info('No tracks found. Exiting.');
        } else {

            if ($this->option('force') || $this->confirm(count($trackFiles) . ' tracks found. Proceed to delete? [y|N]', false)) {

                $count = 0;

                foreach ($trackFiles as $trackFile) {

                    // Set expiration to null (so can be re-cached upon request)
                    $trackFile->expiration = null;
                    $trackFile->update();

                    // Delete files if exists
                    if (File::exists($trackFile->getFile())) {
                        $count++;
                        File::delete($trackFile->getFile());

                        // Remove the cached file sizes for main, trackfile and format
                        Cache::forget($trackFile->getCacheKey('filesize'));
                        Cache::forget($trackFile->track()->getCacheKey('filesize-' . $trackFile->format));
                        Cache::forget($trackFile->track()->album()->getCacheKey('filesize-' . $trackFile->format));

                        $this->info('Deleted ' . $trackFile->getFile());
                    }

                }
                $this->info($count . ' files deleted. Deletion complete. Exiting.');
            } else {
                $this->info('Deletion cancelled. Exiting.');
            }

        }
    }

}