<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Feld0.
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

namespace App\Commands;

use App\Models\Album;
use App\Models\Genre;
use App\Models\Image;
use App\Models\Track;
use App\Models\TrackType;
use App\Models\User;
use AudioCache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use getID3;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ParseTrackTagsCommand extends CommandBase
{
    private $track;
    private $fileToParse;
    private $input;

    public function __construct(Track $track, \Symfony\Component\HttpFoundation\File\File $fileToParse, $inputTags = [])
    {
        $this->track = $track;
        $this->fileToParse = $fileToParse;
        $this->input = $inputTags;
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        $audio = AudioCache::get($this->fileToParse->getPathname());
        list($parsedTags, $rawTags) = $this->parseOriginalTags($this->fileToParse, $this->track->user, $audio->getAudioCodec());
        $this->track->original_tags = ['parsed_tags' => $parsedTags, 'raw_tags' => $rawTags];

        if ($this->input['cover'] !== null) {
            $this->track->cover_id = Image::upload($this->input['cover'], $this->track->user_id)->id;
        } else {
            $this->track->cover_id = $parsedTags['cover_id'];
        }

        $this->track->title = $this->input['title'] ?? $parsedTags['title'] ?? $this->track->title;
        $this->track->track_type_id = $this->input['track_type_id'] ?? TrackType::UNCLASSIFIED_TRACK;

        $this->track->genre_id = isset($this->input['genre'])
            ? $this->getGenreId($this->input['genre'])
            : $parsedTags['genre_id'];

        $this->track->album_id = isset($this->input['album'])
            ? $this->getAlbumId($this->track->user_id, $this->input['album'])
            : $parsedTags['album_id'];

        if ($this->track->album_id === null) {
            $this->track->track_number = null;
        } else {
            $this->track->track_number = filter_var($this->input['track_number'] ?? $parsedTags['track_number'], FILTER_SANITIZE_NUMBER_INT);
            if ($this->track->track_number === null) {
                $this->track->track_number = 1;
            }
        }

        $this->track->released_at = isset($this->input['released_at'])
            ? Carbon::createFromFormat(Carbon::ISO8601, $this->input['released_at'])
            : $parsedTags['release_date'];

        $this->track->description = $this->input['description'] ?? $parsedTags['comments'];
        $this->track->lyrics = $this->input['lyrics'] ?? $parsedTags['lyrics'];

        $this->track->is_vocal = $this->input['is_vocal'] ?? $parsedTags['is_vocal'];
        $this->track->is_explicit = $this->input['is_explicit'] ?? false;
        $this->track->is_downloadable = $this->input['is_downloadable'] ?? true;
        $this->track->is_listed = $this->input['is_listed'] ?? true;

        $this->track = $this->unsetNullVariables($this->track);

        $this->track->save();

        return CommandResponse::succeed();
    }

    /**
     * If a value is null, remove it! Helps prevent weird SQL errors.
     *
     * @param Track
     * @return Track
     */
    private function unsetNullVariables($track)
    {
        $vars = $track->getAttributes();

        foreach ($vars as $key => $value) {
            if ($value === null) {
                unset($track->{"$key"});
            }
        }

        return $track;
    }

    /**
     * Returns the ID of the given genre, creating it if necessary.
     *
     * @param string $genreName
     * @return int
     */
    protected function getGenreId(string $genreName)
    {
        $existingGenre = Genre::withTrashed()
                            ->where('name', $genreName)->first();

        if ($existingGenre == null) {
            // Has never existed, create new genre

            return Genre::create([
                'name' => $genreName,
                'slug' => Str::slug($genreName),
            ])->id;
        } else {
            // Exists in db, has it been deleted?

            $visibleGenre = Genre::where('name', $genreName)->first();

            if ($visibleGenre == null) {
                // This genre was deleted. Let's bring it back
                // instead of creating a new one

                $existingGenre->restore();

                return $existingGenre->id;
            } else {
                // It's fine, just return the ID

                return $visibleGenre->id;
            }
        }
    }

    /**
     * Returns the ID of the given album, creating it if necessary.
     * The cover ID is only used if a new album is created - it will not be
     * written to an existing album.
     *
     * @param int $artistId
     * @param string|null $albumName
     * @param int|null $coverId
     * @return int|null
     */
    protected function getAlbumId(int $artistId, $albumName, $coverId = null)
    {
        if (null !== $albumName) {
            $album = Album::firstOrNew([
                'user_id' => $artistId,
                'title' => $albumName,
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
     * @param \Symfony\Component\HttpFoundation\File\File $file
     * @param User $artist
     * @param string $audioCodec
     * @return array the "processed" and raw tags extracted from the file
     * @throws \Exception
     */
    protected function parseOriginalTags(\Symfony\Component\HttpFoundation\File\File $file, User $artist, string $audioCodec)
    {
        //==========================================================================================================
        // Extract the original tags.
        //==========================================================================================================
        $getId3 = new getID3;

        // all tags read by getID3, including the cover art
        $allTags = $getId3->analyze($file->getPathname());

        // $rawTags => tags specific to a file format (ID3 or Atom), pre-normalization but with cover art removed
        // $parsedTags => normalized tags used by Pony.fm

        if ($audioCodec === 'mp3') {
            list($parsedTags, $rawTags) = $this->getId3Tags($allTags);
        } elseif (Str::startsWith($audioCodec, ['aac', 'alac'])) {
            list($parsedTags, $rawTags) = $this->getAtomTags($allTags);
        } elseif (in_array($audioCodec, ['vorbis', 'flac'])) {
            list($parsedTags, $rawTags) = $this->getVorbisTags($allTags);
        } elseif (Str::startsWith($audioCodec, ['pcm', 'adpcm'])) {
            list($parsedTags, $rawTags) = $this->getAtomTags($allTags);
        } else {
            // Assume the file is untagged if it's in an unknown format.
            $parsedTags = [
                'title' => null,
                'artist' => null,
                'band' => null,
                'genre' => null,
                'track_number' => null,
                'album' => null,
                'year' => null,
                'release_date' => null,
                'comments' => null,
                'lyrics' => null,
            ];
            $rawTags = [];
        }

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

        if ($genreName !== null) {
            $parsedTags['genre_id'] = $this->getGenreId($genreName);
        } else {
            $parsedTags['genre_id'] = null;
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
            $tmpPath = config('ponyfm.files_directory').'/tmp';

            $filename = $file->getFilename().".cover.${extension}";
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
    protected function getId3Tags($rawTags)
    {
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

        $trackNumber = 1;
        if (isset($tags['track_number'])) {
            $trackNumberComponents = explode('/', $tags['track_number'][0]);
            $trackNumber = $trackNumberComponents[0];
        }

        return [
            [
                'title' => isset($tags['title']) ? $tags['title'][0] : null,
                'artist' => isset($tags['artist']) ? $tags['artist'][0] : null,
                'band' => isset($tags['band']) ? $tags['band'][0] : null,
                'genre' => isset($tags['genre']) ? $tags['genre'][0] : null,
                'track_number' => $trackNumber,
                'album' => isset($tags['album']) ? $tags['album'][0] : null,
                'year' => isset($tags['year']) ? (int) $tags['year'][0] : null,
                'release_date' => isset($tags['release_date']) ? $this->parseDateString($tags['release_date'][0]) : null,
                'comments' => $comment,
                'lyrics' => isset($tags['unsynchronised_lyric']) ? $tags['unsynchronised_lyric'][0] : null,
            ],
            $tags,
        ];
    }

    /**
     * @param array $rawTags
     * @return array
     */
    protected function getAtomTags($rawTags)
    {
        if (array_key_exists('tags', $rawTags) && array_key_exists('quicktime', $rawTags['tags'])) {
            $tags = $rawTags['tags']['quicktime'];
        } else {
            $tags = [];
        }

        $trackNumber = 1;
        if (isset($tags['track_number'])) {
            $trackNumberComponents = explode('/', $tags['track_number'][0]);
            $trackNumber = $trackNumberComponents[0];
        }

        if (isset($tags['release_date'])) {
            $releaseDate = $this->parseDateString($tags['release_date'][0]);
        } elseif (isset($tags['creation_date'])) {
            $releaseDate = $this->parseDateString($tags['creation_date'][0]);
        } else {
            $releaseDate = null;
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
                'release_date' => $releaseDate,
                'comments' => isset($tags['comments']) ? $tags['comments'][0] : null,
                'lyrics' => isset($tags['lyrics']) ? $tags['lyrics'][0] : null,
            ],
            $tags,
        ];
    }

    /**
     * @param array $rawTags
     * @return array
     */
    protected function getVorbisTags($rawTags)
    {
        if (array_key_exists('tags', $rawTags) && array_key_exists('vorbiscomment', $rawTags['tags'])) {
            $tags = $rawTags['tags']['vorbiscomment'];
        } else {
            $tags = [];
        }

        $trackNumber = 1;
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
            $tags,
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
    protected function parseDateString(string $dateString)
    {
        switch (Str::length($dateString)) {
            // YYYY
            case 4:
                try {
                    return Carbon::createFromFormat('Y', $dateString)
                        ->month(1)
                        ->day(1);
                } catch (\InvalidArgumentException $e) {
                    return null;
                }

            // YYYY-MM
            case 7:
                try {
                    return Carbon::createFromFormat('Y m', str_replace('-', ' ', $dateString))
                        ->day(1);
                } catch (\InvalidArgumentException $e) {
                    return null;
                }

            // YYYY-MM-DD
            case 10:
                try {
                    return Carbon::createFromFormat('Y m d', str_replace('-', ' ', $dateString));
                } catch (\InvalidArgumentException $e) {
                    return null;
                }
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
