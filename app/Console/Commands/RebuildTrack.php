<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Feld0.
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

use App\Commands\GenerateTrackFilesCommand;
use App\Jobs\EncodeTrackFile;
use App\Models\Track;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesJobs;

class RebuildTrack extends Command
{
    use DispatchesJobs;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rebuild:track
                            {trackId : ID of the track to rebuild}
                            {--upload : Include this option to use the uploaded file as the encode source instead of the master file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-encodes a track\'s files';

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
        /** @var Track $track */
        $track = Track::with('trackFiles')->withTrashed()->find((int) $this->argument('trackId'));
        $this->printTrackInfo($track);

        if ($this->option('upload')) {
            // The track would've been deleted if its original upload failed.
            // It should be restored so the user can publish the track!
            $track->restore();
            $this->info("Attempting to finish this track's upload...");

            $sourceFile = new \SplFileInfo($track->getTemporarySourceFileForVersion($track->current_version));
            $generateTrackFiles = new GenerateTrackFilesCommand($track, $sourceFile, false);
            $result = $generateTrackFiles->execute();
            // The GenerateTrackFiles command will re-encode all TrackFiles.

            if ($result->didFail()) {
                $this->error('Something went wrong!');
                print_r($result->getMessages());
            }
        } else {
            $this->info("Re-encoding this track's files - there should be a line of output for each format!");

            foreach ($track->trackFiles as $trackFile) {
                if (! $trackFile->is_master) {
                    $this->info("Re-encoding this track's {$trackFile->format} file...");
                    $this->dispatch(new EncodeTrackFile($trackFile, true));
                }
            }
        }
    }

    private function printTrackInfo(Track $track)
    {
        $this->comment('Track info:');
        $this->comment("  Title: {$track->title}");
        $this->comment("  Uploaded at: {$track->created_at}");
        $this->comment("  Artist: {$track->user->display_name} [User ID: {$track->user_id}]");
        $this->comment("  Artist email: {$track->user->email}");
    }
}
