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

namespace Poniverse\Ponyfm\Jobs;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use OAuth2\Exception;
use Poniverse\Ponyfm\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Poniverse\Ponyfm\Track;
use Poniverse\Ponyfm\TrackFile;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class EncodeTrackFile extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    /**
     * @var TrackFile
     */
    private $trackFile;
    /**
     * @var
     */
    private $isExpirable;

    /**
     * Create a new job instance.
     * @param TrackFile $trackFile
     * @param $isExpirable
     */
    public function __construct(TrackFile $trackFile, $isExpirable)
    {
        $this->trackFile = $trackFile;
        $this->isExpirable = $isExpirable;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Start the job
        $this->trackFile->in_progress = true;
        $this->trackFile->update();

        // Assign the source to the track's master file
        $source = TrackFile::where('track_id', $this->trackFile->track_id)
            ->where('is_master', true)
            ->first()
            ->getFile();

        // Assign the target
        $destination = $this->trackFile->track->getDirectory();
        $this->trackFile->track->ensureDirectoryExists();
        $target = $destination . '/' . $this->trackFile->getFilename();

        // Prepare the command
        $format = Track::$Formats[$this->trackFile->format];
        $command = $format['command'];
        $command = str_replace('{$source}', '"' . $source . '"', $command);
        $command = str_replace('{$target}', '"' . $target . '"', $command);

        Log::info('Encoding track file ' . $this->trackFile->id . ' into ' . $target);

        // Start a synchronous process to encode the file
        $process = new Process($command);
        try {
            $process->mustRun();
        } catch (ProcessFailedException $e) {
            Log::error('An exception occured in the encoding process for track file ' . $this->trackFile->id . ' - ' . $e->getMessage());
            // Ensure queue fails
            throw $e;
        } finally {
            Log::info($process->getOutput());
        }

        // Update the tags of the track
        $this->trackFile->track->updateTags();

        // Insert the expiration time for cached tracks
        if ($this->isExpirable) {
            $this->trackFile->expiration = Carbon::now()->addMinutes(Config::get('ponyfm.cache_duration'));
            $this->trackFile->update();
        }

        // Complete the job
        $this->trackFile->in_progress = false;
        $this->trackFile->update();

        // Update file size
        $this->trackFile->updateFilesize();

    }

    /**
     * Handle a job failure.
     *
     * @return void
     */
    public function failed()
    {
        $this->trackFile->in_progress = false;
        $this->trackFile->expiration = null;
        $this->trackFile->update();
    }
}
