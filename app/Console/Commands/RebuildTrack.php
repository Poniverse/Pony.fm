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

use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Poniverse\Ponyfm\Commands\GenerateTrackFilesCommand;
use Poniverse\Ponyfm\Commands\UploadTrackCommand;
use Poniverse\Ponyfm\Jobs\EncodeTrackFile;
use Poniverse\Ponyfm\Models\Track;

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
        /** @var Track $track */
        $track = Track::with('trackFiles')->withTrashed()->find((int) $this->argument('trackId'));

        if($this->option('upload')) {
            $this->info("Attempting to finish this track's upload...");
            $sourceFile = new \SplFileInfo($track->getTemporarySourceFile());
            $generateTrackFiles = new GenerateTrackFilesCommand($track, $sourceFile, false);
            $result = $generateTrackFiles->execute();
            // The GenerateTrackFiles command will re-encode all TrackFiles.

            if ($result->didFail()) {
                $this->error("Something went wrong!");
                $this->error(json_encode($result->getMessages(), JSON_PRETTY_PRINT));
            }

        } else {
            foreach ($track->trackFiles as $trackFile) {
                if (!$trackFile->is_master) {
                    $this->info("Re-encoding this track's {$trackFile->format} file...");
                    $this->dispatch(new EncodeTrackFile($trackFile, true));
                }
            }
        }
    }
}
