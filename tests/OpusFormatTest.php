<?php

namespace Tests;

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

use App\Jobs\EncodeTrackFile;
use App\Models\Track;
use App\Models\TrackFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;

class OpusFormatTest extends TestCase
{
    use RefreshDatabase;
    use WithoutMiddleware;

    public function testOpusFormatIsRegistered()
    {
        $format = Track::$Formats['Opus'];

        // `.opus` must stay unique: extension→format resolution is
        // first-match-wins and `.ogg` belongs to Vorbis.
        $this->assertSame('opus', $format['extension']);
        foreach (Track::$Formats as $name => $otherFormat) {
            if ($name !== 'Opus') {
                $this->assertNotSame('opus', $otherFormat['extension']);
            }
        }

        $this->assertSame('audio/ogg', $format['mime_type']);
        $this->assertFalse($format['is_lossless']);
        // The `index` feeds resource_log_items.track_format_id; existing
        // values are frozen, so Opus takes the next free one.
        $this->assertSame(5, $format['index']);

        // Encoded from the lossless source with libopus, 128 kbps VBR, 48 kHz.
        $this->assertStringContainsString('libopus', $format['command']);
        $this->assertStringContainsString('-b:a 128k', $format['command']);
        $this->assertStringContainsString('-vbr on', $format['command']);
        $this->assertStringContainsString('-ar 48000', $format['command']);
        $this->assertStringContainsString('{$source}', $format['command']);

        // Opus is the streaming default, so it must persist on disk like MP3
        // rather than being generated on demand and swept.
        $this->assertNotContains('Opus', Track::$CacheableFormats);
        $this->assertNotContains('Opus', Track::$LosslessFormats);
    }

    public function testUploadCreatesOpusTrackFile()
    {
        $this->callUploadWithParameters(['auto_publish' => false]);

        $this->seeInDatabase('track_files', [
            'track_id' => 1,
            'format' => 'Opus',
            'is_master' => false,
            'is_cacheable' => false,
        ]);
    }

    public function testOpusExtensionResolvesToOpusFormat()
    {
        $this->callUploadWithParameters(['auto_publish' => false]);

        $this->assertSame('Opus', TrackFile::findOrFailByExtension(1, 'opus')->format);
        // Vorbis still owns `.ogg`.
        $this->assertSame('OGG Vorbis', TrackFile::findOrFailByExtension(1, 'ogg')->format);
    }

    public function testStreamsDegradeToMp3UntilOpusFileExists()
    {
        $this->callUploadWithParameters(['auto_publish' => false]);
        // Load through summary(), the column-limited query every track
        // listing uses — it must carry current_version, or getFileFor()
        // probes "-v." paths and suppresses formats that DO exist.
        $track = Track::summary()->findOrFail(1);

        // Backfill hasn't reached this track: opus must not be advertised,
        // and mp3 must be — the player's guaranteed fallback.
        $streams = Track::mapPublicTrackSummary($track)['streams'];
        $this->assertNull($streams['opus']);
        $this->assertNotNull($streams['mp3']);

        // Once the derivative exists on disk, it's advertised.
        $track->ensureDirectoryExists();
        File::put($track->getFileFor('Opus'), 'fake-opus-data');

        $streams = Track::mapPublicTrackSummary($track)['streams'];
        $this->assertSame($track->getStreamUrl('Opus'), $streams['opus']);
    }

    public function testStreamEndpointServesOpusWithRangeSupport()
    {
        config(['ponyfm.use_sendfile' => false]);
        $this->callUploadWithParameters(['auto_publish' => false]);

        $track = Track::findOrFail(1);
        $track->published_at = now();
        $track->save();
        $track->ensureDirectoryExists();
        File::put($track->getFileFor('Opus'), 'fake-opus-data');

        $this->call('GET', "/t{$track->id}/stream.opus");
        $this->assertResponseStatus(200);
        $this->assertSame('audio/ogg', $this->response->headers->get('Content-Type'));

        // Seeking mid-track (radio joins, scrubbing) needs byte ranges to
        // work exactly as they do for MP3.
        $this->call('GET', "/t{$track->id}/stream.opus", [], [], [], ['HTTP_RANGE' => 'bytes=0-3']);
        $this->assertResponseStatus(206);
        $this->assertStringStartsWith('bytes 0-3/', $this->response->headers->get('Content-Range'));
    }

    public function testBackfillCreatesMissingOpusTrackFiles()
    {
        $this->callUploadWithParameters(['auto_publish' => false]);
        $track = Track::findOrFail(1);

        // Simulate a pre-Opus catalogue entry: no Opus row, master on disk.
        $track->trackFiles()->where('format', 'Opus')->forceDelete();
        $track->ensureDirectoryExists();
        File::put($track->trackFiles()->where('is_master', true)->first()->getFile(), 'fake-flac-data');

        Artisan::call('backfill:opus', ['--force' => true]);

        $this->seeInDatabase('track_files', [
            'track_id' => $track->id,
            'format' => 'Opus',
            'is_master' => false,
            'is_cacheable' => false,
            'version' => $track->current_version,
        ]);
        Bus::assertDispatchedSync(EncodeTrackFile::class);
    }

    public function testBackfillDryRunWritesNothing()
    {
        $this->callUploadWithParameters(['auto_publish' => false]);
        $track = Track::findOrFail(1);

        $track->trackFiles()->where('format', 'Opus')->forceDelete();
        $track->ensureDirectoryExists();
        File::put($track->trackFiles()->where('is_master', true)->first()->getFile(), 'fake-flac-data');

        Artisan::call('backfill:opus', ['--dry-run' => true]);

        $this->notSeeInDatabase('track_files', [
            'track_id' => $track->id,
            'format' => 'Opus',
        ]);
        $this->assertStringContainsString('Would encode track #1', Artisan::output());
    }

    public function testBackfillIsIdempotent()
    {
        $this->callUploadWithParameters(['auto_publish' => false]);
        $track = Track::findOrFail(1);

        // The Opus file already exists — a re-run must skip the track.
        $track->ensureDirectoryExists();
        File::put($track->trackFiles()->where('is_master', true)->first()->getFile(), 'fake-flac-data');
        File::put($track->getFileFor('Opus'), 'fake-opus-data');

        Artisan::call('backfill:opus', ['--force' => true]);

        $this->assertStringContainsString('1 already had a valid Opus file', Artisan::output());
        $this->assertSame(
            1,
            TrackFile::where('track_id', $track->id)->where('format', 'Opus')->count()
        );
    }

    public function testBackfillLogsMissingMasterWithoutAborting()
    {
        $this->callUploadWithParameters(['auto_publish' => false]);
        $track = Track::findOrFail(1);

        // No master file on disk: the track must be reported and skipped —
        // never encoded from a lossy derivative, and never a fatal error.
        $track->trackFiles()->where('format', 'Opus')->forceDelete();

        $exitCode = Artisan::call('backfill:opus', ['--force' => true]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('no master file on disk', Artisan::output());
        $this->notSeeInDatabase('track_files', [
            'track_id' => $track->id,
            'format' => 'Opus',
        ]);
    }
}
