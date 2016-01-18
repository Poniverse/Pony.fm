<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Peter Deltchev
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
use DB;
use File;
use Config;
use Log;
use Poniverse\Ponyfm\Exceptions\InvalidEncodeOptionsException;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Poniverse\Ponyfm\Models\Track;
use Poniverse\Ponyfm\Models\TrackFile;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class EncodeTrackFile extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    /**
     * @var TrackFile
     */
    protected $trackFile;
    /**
     * @var
     */
    protected $isExpirable;
    /**
     * @var bool
     */
    protected $isForUpload;
    /**
     * @var bool
     */
    protected $autoPublishWhenComplete;

    /**
     * Create a new job instance.
     * @param TrackFile $trackFile
     * @param bool $isExpirable
     * @param bool $isForUpload indicates whether this encode job is for an upload
     * @param bool $autoPublish
     */
    public function __construct(TrackFile $trackFile, $isExpirable, $isForUpload = false, $autoPublish = false)
    {
        if(
            (!$isForUpload && $trackFile->is_master) ||
            ($isForUpload && $trackFile->is_master && !$trackFile->getFormat()['is_lossless'])
        ) {
            throw new InvalidEncodeOptionsException("Master files cannot be encoded unless we're generating a lossless master file during the upload process.");
        }

        $this->trackFile = $trackFile;
        $this->isExpirable = $isExpirable;
        $this->isForUpload = $isForUpload;
        $this->autoPublishWhenComplete = $autoPublish;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::reconnect();

        // Sanity-check: was this file just generated, or is it already being processed?
        if ($this->trackFile->status === TrackFile::STATUS_PROCESSING) {
            Log::warning('Track file #'.$this->trackFile->id.' (track #'.$this->trackFile->track_id.') is already being processed!');
            return;

        } elseif (!$this->trackFile->is_expired) {
            Log::warning('Track file #'.$this->trackFile->id.' (track #'.$this->trackFile->track_id.') is still valid! No need to re-encode it.');
            return;
        }

        // Start the job
        $this->trackFile->status = TrackFile::STATUS_PROCESSING;
        $this->trackFile->save();

        // Use the track's master file as the source
        if ($this->isForUpload) {
            $source = $this->trackFile->track->getTemporarySourceFile();

        } else {
            $source = TrackFile::where('track_id', $this->trackFile->track_id)
                ->where('is_master', true)
                ->first()
                ->getFile();
        }

        // Assign the target
        $this->trackFile->track->ensureDirectoryExists();
        $target = $this->trackFile->getFile();

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
            Log::info($process->getOutput());
            // Ensure queue fails
            throw $e;
        }

        // Update the tags of the track
        $this->trackFile->track->updateTags($this->trackFile->format);

        // Insert the expiration time for cached tracks
        if ($this->isExpirable && $this->trackFile->is_cacheable) {
            $this->trackFile->expires_at = Carbon::now()->addMinutes(Config::get('ponyfm.track_file_cache_duration'));
            $this->trackFile->save();
        }

        // Update file size
        $this->trackFile->updateFilesize();

        // Complete the job
        $this->trackFile->status = TrackFile::STATUS_NOT_BEING_PROCESSED;
        $this->trackFile->save();

        if ($this->isForUpload) {
            if (!$this->trackFile->is_master && $this->trackFile->is_cacheable) {
                File::delete($this->trackFile->getFile());
            }

            // This was the final TrackFile for this track!
            if ($this->trackFile->track->status === Track::STATUS_COMPLETE) {
                if ($this->autoPublishWhenComplete) {
                    $this->trackFile->track->published_at = Carbon::now();
                    DB::table('tracks')->whereUserId($this->trackFile->track->user_id)->update(['is_latest' => false]);

                    $this->trackFile->track->is_latest = true;
                    $this->trackFile->track->save();
                }

                File::delete($this->trackFile->track->getTemporarySourceFile());
            }
        }
    }

    /**
     * Handle a job failure.
     *
     * @return void
     */
    public function failed()
    {
        $this->trackFile->status = TrackFile::STATUS_PROCESSING_ERROR;
        $this->trackFile->expires_at = null;
        $this->trackFile->save();
    }
}
