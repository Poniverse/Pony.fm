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

use Carbon\Carbon;
use Config;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Input;
use Poniverse\Ponyfm\Album;
use Poniverse\Ponyfm\Exceptions\InvalidEncodeOptionsException;
use Poniverse\Ponyfm\Genre;
use Poniverse\Ponyfm\Image;
use Poniverse\Ponyfm\Jobs\EncodeTrackFile;
use Poniverse\Ponyfm\Track;
use Poniverse\Ponyfm\TrackFile;
use AudioCache;
use File;
use Illuminate\Support\Str;
use Poniverse\Ponyfm\TrackType;

class UploadTrackCommand extends CommandBase
{
    use DispatchesJobs;


    private $_allowLossy;
    private $_allowShortTrack;
    private $_customTrackSource;
    private $_autoPublishByDefault;

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

    public function __construct($allowLossy = false, $allowShortTrack = false, $customTrackSource = null, $autoPublishByDefault = false)
    {
        $this->_allowLossy = $allowLossy;
        $this->_allowShortTrack = $allowShortTrack;
        $this->_customTrackSource = $customTrackSource;
        $this->_autoPublishByDefault = $autoPublishByDefault;
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        return \Auth::user() != null;
    }

    protected function getGenreId(string $genreName) {
        return Genre::firstOrCreate(['name' => $genreName, 'slug' => Str::slug($genreName)])->id;
    }

    protected function getAlbumId(int $artistId, $albumName) {
        if (null !== $albumName) {
            $album = Album::firstOrNew([
                'user_id' => $artistId,
                'title' => $albumName
            ]);

            if (null === $album->id) {
                $album->description = '';
                $album->track_count = 0;
                $album->save();
                return $album->id;
            } else {
                return $album->id;
            }
        } else {
            return null;
        }
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        $user = \Auth::user();
        $trackFile = \Input::file('track', null);

        if (null === $trackFile) {
            return CommandResponse::fail(['track' => ['You must upload an audio file!']]);
        }

        $audio = \AudioCache::get($trackFile->getPathname());


        $track = new Track();
        $track->user_id = $user->id;
        $track->title = Input::get('title', pathinfo($trackFile->getClientOriginalName(), PATHINFO_FILENAME));
        $track->duration = $audio->getDuration();


        $track->save();
        $track->ensureDirectoryExists();

        if (!is_dir(Config::get('ponyfm.files_directory') . '/queued-tracks')) {
            mkdir(Config::get('ponyfm.files_directory') . '/queued-tracks', 0755, true);
        }
        $trackFile = $trackFile->move(Config::get('ponyfm.files_directory').'/queued-tracks', $track->id);

        $input = Input::all();
        $input['track'] = $trackFile;

        $validator = \Validator::make($input, [
            'track' =>
                'required|'
                . ($this->_allowLossy ? '' : 'audio_format:'. implode(',', $this->_losslessFormats).'|')
                . ($this->_allowShortTrack ? '' : 'min_duration:30|')
                . 'audio_channels:1,2',

            'auto_publish'      => 'boolean',
            'title'             => 'string',
            'track_type_id'     => 'exists:track_types,id',
            'genre'             => 'string',
            'album'             => 'string',
            'track_number'      => 'integer',
            'released_at'       => 'date_format:'.Carbon::ISO8601,
            'description'       => 'string',
            'lyrics'            => 'string',
            'is_vocal'          => 'boolean',
            'is_explicit'       => 'boolean',
            'is_downloadable'   => 'boolean',
            'is_listed'         => 'boolean',
            'cover'             => 'image|mimes:png,jpeg|min_width:350|min_height:350',
            'metadata'          => 'json',
        ]);

        if ($validator->fails()) {
            $track->delete();
            return CommandResponse::fail($validator);
        }


        // Process optional track fields
        $autoPublish = (bool) Input::get('auto_publish', $this->_autoPublishByDefault);

        $track->title = Input::get('title', $track->title);
        $track->track_type_id = Input::get('track_type_id', TrackType::UNCLASSIFIED_TRACK);
        $track->genre_id = $this->getGenreId(Input::get('genre', 'Unknown'));
        $track->album_id = $this->getAlbumId($user->id, Input::get('album', null));
        $track->track_number = $track->album_id !== null ? (int) Input::get('track_number', null) : null;
        $track->released_at = Input::has('released_at') ? Carbon::createFromFormat(Carbon::ISO8601, Input::get('released_at')) : null;
        $track->description = Input::get('description', '');
        $track->lyrics = Input::get('lyrics', '');
        $track->is_vocal = (bool) Input::get('is_vocal');
        $track->is_explicit = (bool) Input::get('is_explicit');
        $track->is_downloadable = (bool) Input::get('is_downloadable');
        $track->is_listed = (bool) Input::get('is_listed', true);

        $track->source = $this->_customTrackSource ?? 'direct_upload';

        if (Input::hasFile('cover')) {
            $track->cover_id = Image::upload(Input::file('cover'), $track->user_id)->id;
        }

        // If json_decode() isn't called here, Laravel will surround the JSON
        // string with quotes when storing it in the database, which breaks things.
        $track->metadata = json_decode(Input::get('metadata', null));

        $track->save();



        try {
            $source = $trackFile->getPathname();

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
                    $track->delete();
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


            $trackFiles = [];

            foreach (Track::$Formats as $name => $format) {
                // Don't bother with lossless transcodes of lossy uploads, and
                // don't re-encode the lossy master.
                if ($isLossyUpload && ($format['is_lossless'] || $name === $masterFormat)) {
                    continue;
                }

                $trackFile = new TrackFile();
                $trackFile->is_master = $name === 'FLAC' ? true : false;
                $trackFile->format = $name;
                $trackFile->status = TrackFile::STATUS_PROCESSING;

                if (in_array($name, Track::$CacheableFormats) && $trackFile->is_master == false) {
                    $trackFile->is_cacheable = true;
                } else {
                    $trackFile->is_cacheable = false;
                }
                $track->trackFiles()->save($trackFile);

                // All TrackFile records we need are synchronously created
                // before kicking off the encode jobs in order to avoid a race
                // condition with the "temporary" source file getting deleted.
                $trackFiles[] = $trackFile;
            }

            try {
                foreach($trackFiles as $trackFile)  {
                    $this->dispatch(new EncodeTrackFile($trackFile, false, true, $autoPublish));
                }

            } catch (InvalidEncodeOptionsException $e) {
                $track->delete();
                return CommandResponse::fail(['track' => [$e->getMessage()]]);
            }

        } catch (\Exception $e) {
            $track->delete();
            throw $e;
        }

        return CommandResponse::succeed([
            'id' => $track->id,
            'name' => $track->name,
            'title' => $track->title,
            'slug' => $track->slug,
            'autoPublish' => $autoPublish,
        ]);
    }
}
