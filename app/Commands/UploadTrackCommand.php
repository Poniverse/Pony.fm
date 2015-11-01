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

namespace Poniverse\Ponyfm\Commands;

use Poniverse\Ponyfm\Track;
use Poniverse\Ponyfm\TrackFile;
use AudioCache;
use File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class UploadTrackCommand extends CommandBase
{
    private $_allowLossy;
    private $_allowShortTrack;
    private $_losslessFormats = [
        'flac',
        'pcm_s16le ([1][0][0][0] / 0x0001)',
        'pcm_s16be',
        'adpcm_ms ([2][0][0][0] / 0x0002)',
        'pcm_s24le ([1][0][0][0] / 0x0001)',
        'pcm_s24be',
        'pcm_f32le ([3][0][0][0] / 0x0003)',
        'pcm_f32be (fl32 / 0x32336C66)'
    ];

    public function __construct($allowLossy = false, $allowShortTrack = false)
    {
        $this->_allowLossy = $allowLossy;
        $this->_allowShortTrack = $allowShortTrack;
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        return \Auth::user() != null;
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        $user = \Auth::user();
        $trackFile = \Input::file('track');
        $audio = \AudioCache::get($trackFile->getPathname());

        $validator = \Validator::make(['track' => $trackFile], [
            'track' =>
                'required|'
                . ($this->_allowLossy ? '' : 'audio_format:'. implode(',', $this->_losslessFormats).'|')
                . ($this->_allowShortTrack ? '' : 'min_duration:30|')
                . 'audio_channels:1,2'
        ]);

        if ($validator->fails()) {
            return CommandResponse::fail($validator);
        }

        $track = new Track();

        try {
            $track->user_id = $user->id;
            $track->title = pathinfo($trackFile->getClientOriginalName(), PATHINFO_FILENAME);
            $track->duration = $audio->getDuration();
            $track->is_listed = true;

            $track->save();

            $destination = $track->getDirectory();
            $track->ensureDirectoryExists();

            $source = $trackFile->getPathname();
            $index = 0;

            // Lossy uploads need to be identified and set as the master file
            // without being re-encoded.
            $audioObject = AudioCache::get($source);
            $isLossyUpload = !in_array($audioObject->getAudioCodec(), $this->_losslessFormats);

            if ($isLossyUpload) {
                if ($audioObject->getAudioCodec() === 'mp3') {
                    $masterFormat = 'MP3';

                } else if (Str::startsWith($audioObject->getAudioCodec(), 'aac')) {
                    $masterFormat = 'AAC';

                } else if ($audioObject->getAudioCodec() === 'vorbis') {
                    $masterFormat = 'OGG Vorbis';

                } else {
                    $validator->messages()->add('track', 'The track does not contain audio in a known lossy format.');
                    return CommandResponse::fail($validator);
                }

                $trackFile = new TrackFile();
                $trackFile->is_master = true;
                $trackFile->format = $masterFormat;
                $trackFile->track_id = $track->id;
                $trackFile->save();

                // Lossy masters are copied into the datastore - no re-encoding involved.
                File::copy($source, $trackFile->getFile());
            }

            foreach (Track::$Formats as $name => $format) {
                // Don't bother with lossless transcodes of lossy uploads, and
                // don't re-encode the lossy master.
                if ($isLossyUpload && ($format['is_lossless'] || $name === $masterFormat)) {
                    continue;
                }

                $trackFile = new TrackFile();
                $trackFile->is_master = $name === 'FLAC' ? true : false;
                $trackFile->format = $name;
                if (in_array($name, Track::$CacheableFormats) && $trackFile->is_master == false) {
                    $trackFile->is_cacheable = true;
                } else {
                    $trackFile->is_cacheable = false;
                }
                $track->trackFiles()->save($trackFile);

                // Encode track file
                $target = $trackFile->getFile();

                $command = $format['command'];
                $command = str_replace('{$source}', '"' . $source . '"', $command);
                $command = str_replace('{$target}', '"' . $target . '"', $command);

                Log::info('Encoding ' . $track->id . ' into ' . $target);
                $this->notify('Encoding ' . $name, $index / count(Track::$Formats) * 100);

                $process = new Process($command);
                $process->mustRun();

                // Update file size for track file
                $trackFile->updateFilesize();

                // Delete track file if it is cacheable
                if ($trackFile->is_cacheable == true) {
                    File::delete($trackFile->getFile());
                }
            }

            $track->updateTags();

        } catch (\Exception $e) {
            $track->delete();
            throw $e;
        }

        return CommandResponse::succeed([
            'id' => $track->id,
            'name' => $track->name
        ]);
    }
}
