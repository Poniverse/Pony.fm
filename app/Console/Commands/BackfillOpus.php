<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2026 Poniverse.
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

use App\Jobs\EncodeTrackFile;
use App\Models\Track;
use App\Models\TrackFile;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class BackfillOpus extends Command
{
    use DispatchesJobs;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backfill:opus
                            {--dry-run : Report what would be done without writing or encoding anything.}
                            {--force : Skip all prompts.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates the Opus derivative for every track that doesn\'t have one yet. Idempotent and resumable: tracks whose Opus file already exists are skipped, and failures are logged without aborting the run.';

    private const FORMAT = 'Opus';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        if (! $isDryRun && ! $this->option('force') && ! $this->confirm(
            'This will encode an Opus file for every track in the catalogue that lacks one. It may take a long time. Proceed?'
        )) {
            return 0;
        }

        $counts = [
            'skipped' => 0,
            'encoded' => 0,
            'failed' => 0,
            'missing_master' => 0,
        ];

        Track::query()->orderBy('id')->chunkById(100, function ($tracks) use ($isDryRun, &$counts) {
            foreach ($tracks as $track) {
                $this->processTrack($track, $isDryRun, $counts);
            }
        });

        $verb = $isDryRun ? 'would be encoded' : 'encoded';
        $this->info("Done! {$counts['encoded']} {$verb}, {$counts['skipped']} already had a valid Opus file, "
            ."{$counts['missing_master']} skipped for missing master files, {$counts['failed']} failed.");

        if ($counts['failed'] > 0 || $counts['missing_master'] > 0) {
            $this->warn('Failures were logged; re-running this command will retry them.');
        }

        return 0;
    }

    private function processTrack(Track $track, bool $isDryRun, array &$counts)
    {
        $trackFile = $track->trackFiles()
            ->where('format', self::FORMAT)
            ->where('version', $track->current_version)
            ->first();

        // Idempotence/resumability: a track whose Opus file is already on
        // disk needs no encode — but heal row metadata left behind by an
        // interrupted or failed earlier run.
        if ($trackFile !== null && File::exists($trackFile->getFile())) {
            if ((int) $trackFile->status !== TrackFile::STATUS_NOT_BEING_PROCESSED || $trackFile->filesize === null) {
                $trackFile->status = TrackFile::STATUS_NOT_BEING_PROCESSED;
                $trackFile->save();
                $trackFile->updateFilesize();
            }
            $counts['skipped']++;

            return;
        }

        // The encode source is always the master file — never a lossy
        // derivative.
        $master = $track->trackFiles()
            ->where('is_master', true)
            ->where('version', $track->current_version)
            ->first();

        if ($master === null || ! File::exists($master->getFile())) {
            $counts['missing_master']++;
            $message = "Track #{$track->id} has no master file on disk; cannot encode Opus.";
            Log::warning('[backfill:opus] '.$message);
            $this->warn($message);

            return;
        }

        if ($isDryRun) {
            $counts['encoded']++;
            $this->line("Would encode track #{$track->id} ({$track->title})");

            return;
        }

        if ($trackFile === null) {
            $trackFile = new TrackFile();
            $trackFile->is_master = false;
            $trackFile->format = self::FORMAT;
            $trackFile->status = TrackFile::STATUS_PROCESSING_PENDING;
            $trackFile->version = $track->current_version;
            $trackFile->is_cacheable = false;
            $track->trackFilesForAllVersions()->save($trackFile);
        } else {
            // A row without a file is a previous failure or an interrupted
            // run — reset it so the encode isn't skipped as "in progress".
            $trackFile->status = TrackFile::STATUS_PROCESSING_PENDING;
            $trackFile->expires_at = null;
            $trackFile->save();
        }

        try {
            $this->dispatchSync(new EncodeTrackFile($trackFile, false));
            $counts['encoded']++;
            $this->info("Encoded track #{$track->id} ({$track->title})");
        } catch (\Throwable $e) {
            // Log and carry on — one broken source file must not abort a
            // catalogue-wide run.
            $counts['failed']++;
            $trackFile->status = TrackFile::STATUS_PROCESSING_ERROR;
            $trackFile->save();
            $message = "Track #{$track->id} failed to encode: {$e->getMessage()}";
            Log::error('[backfill:opus] '.$message);
            $this->error($message);
        }
    }
}
