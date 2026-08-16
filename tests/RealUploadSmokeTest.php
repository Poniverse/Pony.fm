<?php

/**
 * Opt-in smoke test: runs a real audio file through the FULL upload
 * pipeline — unlike ApiTest, nothing is faked, so the ffmpeg encodes and
 * tag writers (metaflac/vorbiscomment/AtomicParsley) actually execute.
 *
 * Skipped unless PONYFM_SMOKE_FILE points at an audio file:
 *   PATH="$PWD/docker/bin:$PATH" PONYFM_SMOKE_FILE=/path/to/song.flac \
 *     php artisan test --filter=RealUploadSmokeTest
 */

namespace Tests;

use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Http\UploadedFile;

class RealUploadSmokeTest extends TestCase
{
    use RefreshDatabase;
    use WithoutMiddleware;

    public function testRealFlacUploadProcessesFully()
    {
        $source = getenv('PONYFM_SMOKE_FILE');
        if (! $source || ! file_exists($source)) {
            $this->markTestSkipped('Set PONYFM_SMOKE_FILE to a real audio file.');
        }

        $user = User::factory()->create();

        $tmp = storage_path('app/testing-datastore/tmp/'.basename($source));
        @mkdir(dirname($tmp), 0777, true);
        copy($source, $tmp);
        $file = new UploadedFile($tmp, basename($source), null, null, true);

        $this->actingAs($user)
            ->call('POST', '/api/web/tracks/upload', [], [], ['track' => $file]);

        $this->assertResponseStatus(200);
        $json = json_decode($this->response->getContent(), true);

        $track = Track::findOrFail($json['id']);
        $files = $track->trackFilesForAllVersions()->get();
        $this->assertSame(Track::STATUS_COMPLETE, $track->status, 'Track processing did not complete');
        $this->assertGreaterThan(0, $files->count(), 'No track files were generated');

        $unprocessed = $files->where('status', \App\Models\TrackFile::STATUS_PROCESSING)->count();
        fwrite(STDERR, "\nUploaded '{$json['title']}' -> track #{$json['id']}, formats: "
            .$files->pluck('format')->implode(', ')." ({$unprocessed} still processing)\n");

        // Streaming with sendfile off: PHP must serve real bytes itself.
        config(['ponyfm.use_sendfile' => false]);
        $this->actingAs($user)->call('GET', "/t{$track->id}/stream.mp3");
        $this->assertResponseStatus(200);
        $this->assertInstanceOf(
            \Symfony\Component\HttpFoundation\BinaryFileResponse::class,
            $this->response->baseResponse,
            'Stream did not return file contents with USE_SENDFILE=false'
        );
        fwrite(STDERR, 'Streamed '.$this->response->baseResponse->getFile()->getSize()." bytes of MP3 without sendfile\n");
    }
}
