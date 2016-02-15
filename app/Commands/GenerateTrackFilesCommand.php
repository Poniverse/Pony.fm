<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Peter Deltchev
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

use FFmpegMovie;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Poniverse\Ponyfm\Exceptions\InvalidEncodeOptionsException;
use Poniverse\Ponyfm\Jobs\EncodeTrackFile;
use Poniverse\Ponyfm\Models\Track;
use Poniverse\Ponyfm\Models\TrackFile;
use AudioCache;
use File;
use Illuminate\Support\Str;
use SplFileInfo;
use Validator;

/**
 * This command is the "second phase" of the upload process - once metadata has
 * been parsed and the track object is created, this generates the track's
 * corresponding TrackFile objects and ensures that all of them have been encoded.
 *
 * @package Poniverse\Ponyfm\Commands
 */
class GenerateTrackFilesCommand extends CommandBase
{
    use DispatchesJobs;

    private $track;
    private $autoPublish;
    private $sourceFile;

    static $_losslessFormats = [
        'flac',
        'pcm',
        'adpcm',
    ];

    public function __construct(Track $track, SplFileInfo $sourceFile, bool $autoPublish = false, int $reprocessTrackId = null)
    {
        $this->track = $track;
        $this->autoPublish = $autoPublish;
        $this->sourceFile = $sourceFile;
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        try {
            $source = $this->sourceFile->getPathname();

            // Lossy uploads need to be identified and set as the master file
            // without being re-encoded.
            $audioObject = AudioCache::get($source);
            $isLossyUpload = !$this->isLosslessFile($audioObject);

            if ($isLossyUpload) {
                if ($audioObject->getAudioCodec() === 'mp3') {
                    $masterFormat = 'MP3';

                } else if (Str::startsWith($audioObject->getAudioCodec(), 'aac')) {
                    $masterFormat = 'AAC';

                } else if ($audioObject->getAudioCodec() === 'vorbis') {
                    $masterFormat = 'OGG Vorbis';

                } else {
                    $validator = new Validator();
                    $validator->messages()->add('track', 'The track does not contain audio in a known lossy format.');
                    $this->track->delete();
                    return CommandResponse::fail($validator);
                }

                // Sanity check: skip creating this TrackFile if it already exists.
                $trackFile = $this->trackFileExists($masterFormat);

                if (!$trackFile) {
                    $trackFile = new TrackFile();
                    $trackFile->is_master = true;
                    $trackFile->format = $masterFormat;
                    $trackFile->track_id = $this->track->id;
                    $trackFile->save();
                }

                // Lossy masters are copied into the datastore - no re-encoding involved.
                File::copy($source, $trackFile->getFile());
            }


            $trackFiles = [];

            foreach (Track::$Formats as $name => $format) {
                // Don't bother with lossless transcodes of lossy uploads, and
                // don't re-encode the lossy master.
                if ($isLossyUpload && ($format['is_lossless'] || $name === $masterFormat)) {
                    continue;
                }

                // Sanity check: skip creating this TrackFile if it already exists.
                //               But, we'll still encode it!
                if ($trackFile = $this->trackFileExists($name)) {
                    $trackFiles[] = $trackFile;
                    continue;
                }

                $trackFile = new TrackFile();
                $trackFile->is_master = $name === 'FLAC' ? true : false;
                $trackFile->format = $name;
                $trackFile->status = TrackFile::STATUS_PROCESSING_PENDING;

                if (in_array($name, Track::$CacheableFormats) && $trackFile->is_master == false) {
                    $trackFile->is_cacheable = true;
                } else {
                    $trackFile->is_cacheable = false;
                }
                $this->track->trackFiles()->save($trackFile);

                // All TrackFile records we need are synchronously created
                // before kicking off the encode jobs in order to avoid a race
                // condition with the "temporary" source file getting deleted.
                $trackFiles[] = $trackFile;
            }

            try {
                foreach ($trackFiles as $trackFile) {
                    $this->dispatch(new EncodeTrackFile($trackFile, false, true, $this->autoPublish));
                }

            } catch (InvalidEncodeOptionsException $e) {
                $this->track->delete();
                return CommandResponse::fail(['track' => [$e->getMessage()]]);
            }

        } catch (\Exception $e) {
            $this->track->delete();
            throw $e;
        }

        return CommandResponse::succeed([
            'id' => $this->track->id,
            'name' => $this->track->name,
            'title' => $this->track->title,
            'slug' => $this->track->slug,
            'autoPublish' => $this->autoPublish,
        ]);
    }

    /**
     * @param FFmpegMovie|string $file object or full path of the file we're checking
     * @return bool whether the given file is lossless
     */
    private function isLosslessFile($file) {
        if (is_string($file)) {
            $file = AudioCache::get($file);
        }

        return Str::startsWith($file->getAudioCodec(), static::$_losslessFormats);
    }

    /**
     * @param string $format
     * @return TrackFile|null
     */
    private function trackFileExists(string $format) {
        return $this->track->trackFiles()->where('format', $format)->first();
    }
}
