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
use getID3;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Input;
use Poniverse\Ponyfm\Models\Album;
use Poniverse\Ponyfm\Exceptions\InvalidEncodeOptionsException;
use Poniverse\Ponyfm\Models\Genre;
use Poniverse\Ponyfm\Models\Image;
use Poniverse\Ponyfm\Jobs\EncodeTrackFile;
use Poniverse\Ponyfm\Models\Track;
use Poniverse\Ponyfm\Models\TrackFile;
use AudioCache;
use File;
use Illuminate\Support\Str;
use Poniverse\Ponyfm\Models\TrackType;
use Poniverse\Ponyfm\Models\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UploadTrackCommand extends CommandBase
{
    use DispatchesJobs;


    private $_allowLossy;
    private $_allowShortTrack;
    private $_customTrackSource;
    private $_autoPublishByDefault;

    private $_losslessFormats = [
        'flac',
        'pcm',
        'adpcm',
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
        list($parsedTags, $rawTags) = $this->parseOriginalTags($trackFile, $user, $audio->getAudioCodec());


        $track = new Track();
        $track->user_id = $user->id;
        $track->title = Input::get('title', $parsedTags['title']);
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
                . ($this->_allowLossy
                    ? 'audio_format:flac,pcm,adpcm,aac,mp3,vorbis|'
                    : 'audio_format:flac,pcm,adpcm|')
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
        $autoPublish = (bool) ($input['auto_publish'] ?? $this->_autoPublishByDefault);

        if (Input::hasFile('cover')) {
            $track->cover_id = Image::upload(Input::file('cover'), $track->user_id)->id;
        } else {
            $track->cover_id = $parsedTags['cover_id'];
        }

        $track->title           = $input['title'] ?? $parsedTags['title'] ?? $track->title;
        $track->track_type_id   = $input['track_type_id'] ?? TrackType::UNCLASSIFIED_TRACK;

        $track->genre_id = isset($input['genre'])
            ? $this->getGenreId($input['genre'])
            : $parsedTags['genre_id'];

        $track->album_id = isset($input['album'])
            ? $this->getAlbumId($user->id, $input['album'])
            : $parsedTags['album_id'];

        if ($track->album_id === null) {
            $track->track_number = null;
        } else {
            $track->track_number = $input['track_number'] ?? $parsedTags['track_number'];
        }

        $track->released_at = isset($input['released_at'])
            ? Carbon::createFromFormat(Carbon::ISO8601, $input['released_at'])
            : $parsedTags['release_date'];

        $track->description     = $input['description'] ?? $parsedTags['comments'];
        $track->lyrics          = $input['lyrics'] ?? $parsedTags['lyrics'];

        $track->is_vocal        = $input['is_vocal'] ?? $parsedTags['is_vocal'];
        $track->is_explicit     = $input['is_explicit'] ?? false;
        $track->is_downloadable = $input['is_downloadable'] ?? true;
        $track->is_listed       = $input['is_listed'] ?? true;
        $track->source          = $this->_customTrackSource ?? 'direct_upload';

        // If json_decode() isn't called here, Laravel will surround the JSON
        // string with quotes when storing it in the database, which breaks things.
        $track->metadata = json_decode(Input::get('metadata', null));
        $track->original_tags = ['parsed_tags' => $parsedTags, 'raw_tags' => $rawTags];

        $track->save();


        try {
            $source = $trackFile->getPathname();

            // Lossy uploads need to be identified and set as the master file
            // without being re-encoded.
            $audioObject = AudioCache::get($source);
            $isLossyUpload = !Str::startsWith($audioObject->getAudioCodec(), $this->_losslessFormats);

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
                $trackFile->status = TrackFile::STATUS_PROCESSING_PENDING;

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

    /**
     * Returns the ID of the given genre, creating it if necessary.
     *
     * @param string $genreName
     * @return int
     */
    protected function getGenreId(string $genreName) {
        return Genre::firstOrCreate([
            'name' => $genreName,
            'slug' => Str::slug($genreName)
        ])->id;
    }

    /**
     * Returns the ID of the given album, creating it if necessary.
     * The cover ID is only used if a new album is created - it will not be
     * written to an existing album.
     *
     * @param int $artistId
     * @param string|null $albumName
     * @param null $coverId
     * @return int|null
     */
    protected function getAlbumId(int $artistId, $albumName, $coverId = null) {
        if (null !== $albumName) {
            $album = Album::firstOrNew([
                'user_id' => $artistId,
                'title' => $albumName
            ]);

            if (null === $album->id) {
                $album->description = '';
                $album->track_count = 0;
                $album->cover_id = $coverId;
                $album->save();
            }

            return $album->id;
        } else {
            return null;
        }
    }

    /**
     * Extracts a file's tags.
     *
     * @param UploadedFile $file
     * @param User $artist
     * @param string $audioCodec
     * @return array the "processed" and raw tags extracted from the file
     * @throws \Exception
     */
    protected function parseOriginalTags(UploadedFile $file, User $artist, string $audioCodec) {
        //==========================================================================================================
        // Extract the original tags.
        //==========================================================================================================
        $getId3 = new getID3;

        // all tags read by getID3, including the cover art
        $allTags = $getId3->analyze($file->getPathname());

        // tags specific to a file format (ID3 or Atom), pre-normalization but with cover art removed
        $rawTags = [];

        // normalized tags used by Pony.fm
        $parsedTags = [];

        if ($audioCodec === 'mp3') {
            list($parsedTags, $rawTags) = $this->getId3Tags($allTags);

        } elseif (Str::startsWith($audioCodec, 'aac')) {
            list($parsedTags, $rawTags) = $this->getAtomTags($allTags);

        } elseif ($audioCodec === 'vorbis') {
            list($parsedTags, $rawTags) = $this->getVorbisTags($allTags);

        } elseif ($audioCodec === 'flac') {
            list($parsedTags, $rawTags) = $this->getVorbisTags($allTags);

        } elseif (Str::startsWith($audioCodec, ['pcm', 'adpcm'])) {
            list($parsedTags, $rawTags) = $this->getAtomTags($allTags);

        }


        //==========================================================================================================
        // Fill in the title tag if it's missing
        //==========================================================================================================
        $parsedTags['title'] = $parsedTags['title'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);


        //==========================================================================================================
        // Determine the release date.
        //==========================================================================================================
        if ($parsedTags['release_date'] === null && $parsedTags['year'] !== null) {
            $parsedTags['release_date'] = Carbon::create($parsedTags['year'], 1, 1);
        }

        //==========================================================================================================
        // Does this track have vocals?
        //==========================================================================================================
        $parsedTags['is_vocal'] = $parsedTags['lyrics'] !== null;


        //==========================================================================================================
        // Determine the genre.
        //==========================================================================================================
        $genreName = $parsedTags['genre'];

        if ($genreName) {
            $parsedTags['genre_id'] = $this->getGenreId($genreName);

        } else {
            $parsedTags['genre_id'] = $this->getGenreId('Unknown');
        }

        //==========================================================================================================
        // Extract the cover art, if any exists.
        //==========================================================================================================

        $coverId = null;
        if (array_key_exists('comments', $allTags) && array_key_exists('picture', $allTags['comments'])) {
            $image = $allTags['comments']['picture'][0];

            if ($image['image_mime'] === 'image/png') {
                $extension = 'png';

            } elseif ($image['image_mime'] === 'image/jpeg') {
                $extension = 'jpg';

            } else {
                throw new BadRequestHttpException('Unknown cover format embedded in the track file!');
            }

            // write temporary image file
            $tmpPath = Config::get('ponyfm.files_directory') . '/tmp';

            $filename = $file->getFilename() . ".cover.${extension}";
            $imageFilePath = "${tmpPath}/${filename}";

            File::put($imageFilePath, $image['data']);
            $imageFile = new UploadedFile($imageFilePath, $filename, $image['image_mime']);

            $cover = Image::upload($imageFile, $artist);
            $coverId = $cover->id;

        } else {
            // no cover art was found - carry on
        }

        $parsedTags['cover_id'] = $coverId;


        //==========================================================================================================
        // Is this part of an album?
        //==========================================================================================================
        $albumId = null;
        $albumName = $parsedTags['album'];

        if ($albumName !== null) {
            $albumId = $this->getAlbumId($artist->id, $albumName, $coverId);
        }

        $parsedTags['album_id'] = $albumId;


        return [$parsedTags, $rawTags];
    }


    /**
     * @param array $rawTags
     * @return array
     */
    protected function getId3Tags($rawTags) {
        if (array_key_exists('tags', $rawTags) && array_key_exists('id3v2', $rawTags['tags'])) {
            $tags = $rawTags['tags']['id3v2'];
        } elseif (array_key_exists('tags', $rawTags) && array_key_exists('id3v1', $rawTags['tags'])) {
            $tags = $rawTags['tags']['id3v1'];
        } else {
            $tags = [];
        }


        $comment = null;

        if (isset($tags['comment'])) {
            // The "comment" tag comes in with a badly encoded string index
            // so its array key has to be used implicitly.
            $key = array_keys($tags['comment'])[0];

            // The comment may have a null byte at the end. trim() removes it.
            $comment = trim($tags['comment'][$key]);

            // Replace the malformed comment with the "fixed" one.
            unset($tags['comment'][$key]);
            $tags['comment'][0] = $comment;
        }

        return [
            [
                'title' => isset($tags['title']) ? $tags['title'][0] : null,
                'artist' => isset($tags['artist']) ? $tags['artist'][0] : null,
                'band' => isset($tags['band']) ? $tags['band'][0] : null,
                'genre' => isset($tags['genre']) ? $tags['genre'][0] : null,
                'track_number' => isset($tags['track_number']) ? $tags['track_number'][0] : null,
                'album' => isset($tags['album']) ? $tags['album'][0] : null,
                'year' => isset($tags['year']) ? (int) $tags['year'][0] : null,
                'release_date' => isset($tags['release_date']) ? $this->parseDateString($tags['release_date'][0]) : null,
                'comments' => $comment,
                'lyrics' => isset($tags['unsynchronised_lyric']) ? $tags['unsynchronised_lyric'][0] : null,
            ],
            $tags
        ];
    }

    /**
     * @param array $rawTags
     * @return array
     */
    protected function getAtomTags($rawTags) {
        if (array_key_exists('tags', $rawTags) && array_key_exists('quicktime', $rawTags['tags'])) {
            $tags = $rawTags['tags']['quicktime'];
        } else {
            $tags = [];
        }

        $trackNumber = null;
        if (isset($tags['track_number'])) {
            $trackNumberComponents = explode('/', $tags['track_number'][0]);
            $trackNumber = $trackNumberComponents[0];
        }

        return [
            [
                'title' => isset($tags['title']) ? $tags['title'][0] : null,
                'artist' => isset($tags['artist']) ? $tags['artist'][0] : null,
                'band' => isset($tags['band']) ? $tags['band'][0] : null,
                'album_artist' => isset($tags['album_artist']) ? $tags['album_artist'][0] : null,
                'genre' => isset($tags['genre']) ? $tags['genre'][0] : null,
                'track_number' => $trackNumber,
                'album' => isset($tags['album']) ? $tags['album'][0] : null,
                'year' => isset($tags['year']) ? (int) $tags['year'][0] : null,
                'release_date' => isset($tags['release_date']) ? $this->parseDateString($tags['release_date'][0]) : null,
                'comments' => isset($tags['comments']) ? $tags['comments'][0] : null,
                'lyrics' => isset($tags['lyrics']) ? $tags['lyrics'][0] : null,
            ],
            $tags
        ];
    }

    /**
     * @param array $rawTags
     * @return array
     */
    protected function getVorbisTags($rawTags) {
        if (array_key_exists('tags', $rawTags) && array_key_exists('vorbiscomment', $rawTags['tags'])) {
            $tags = $rawTags['tags']['vorbiscomment'];
        } else {
            $tags = [];
        }

        $trackNumber = null;
        if (isset($tags['track_number'])) {
            $trackNumberComponents = explode('/', $tags['track_number'][0]);
            $trackNumber = $trackNumberComponents[0];
        }

        return [
            [
                'title' => isset($tags['title']) ? $tags['title'][0] : null,
                'artist' => isset($tags['artist']) ? $tags['artist'][0] : null,
                'band' => isset($tags['band']) ? $tags['band'][0] : null,
                'album_artist' => isset($tags['album_artist']) ? $tags['album_artist'][0] : null,
                'genre' => isset($tags['genre']) ? $tags['genre'][0] : null,
                'track_number' => $trackNumber,
                'album' => isset($tags['album']) ? $tags['album'][0] : null,
                'year' => isset($tags['year']) ? (int) $tags['year'][0] : null,
                'release_date' => isset($tags['date']) ? $this->parseDateString($tags['date'][0]) : null,
                'comments' => isset($tags['comments']) ? $tags['comments'][0] : null,
                'lyrics' => isset($tags['lyrics']) ? $tags['lyrics'][0] : null,
            ],
            $tags
        ];
    }

    /**
     * Parses a potentially-partial date string into a proper date object.
     *
     * The tagging formats we deal with base their date format on ISO 8601, but
     * the timestamp may be incomplete.
     *
     * @link https://code.google.com/p/mp4v2/wiki/iTunesMetadata
     * @link https://wiki.xiph.org/VorbisComment#Date_and_time
     * @link http://id3.org/id3v2.4.0-frames
     *
     * @param string $dateString
     * @return null|Carbon
     */
    protected function parseDateString(string $dateString) {
        switch (Str::length($dateString)) {
            // YYYY
            case 4:
                return Carbon::createFromFormat('Y', $dateString)
                    ->month(1)
                    ->day(1);

            // YYYY-MM
            case 7:
                return Carbon::createFromFormat('Y-m', $dateString)
                    ->day(1);

            // YYYY-MM-DD
            case 10:
                return Carbon::createFromFormat('Y-m-d', $dateString);
                break;

            default:
                // We might have an ISO-8601 string in our hooves.
                // If not, give up.
                try {
                    return Carbon::createFromFormat(Carbon::ISO8601, $dateString);

                } catch (\InvalidArgumentException $e) {
                    return null;
                }
        }
    }
}
