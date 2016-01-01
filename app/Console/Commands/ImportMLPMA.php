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

namespace Poniverse\Ponyfm\Console\Commands;

use Poniverse\Ponyfm\Models\Album;
use Poniverse\Ponyfm\Commands\UploadTrackCommand;
use Poniverse\Ponyfm\Models\Genre;
use Poniverse\Ponyfm\Models\Image;
use Poniverse\Ponyfm\Models\Track;
use Poniverse\Ponyfm\Models\User;
use Auth;
use Carbon\Carbon;
use Config;
use DB;
use File;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Input;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use getID3;


class ImportMLPMA extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mlpma:import
                            {--startAt=1 : Track to start importing from. Useful for resuming an interrupted import.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports the MLP Music Archive';

    /**
     * File extensions to ignore when importing the archive.
     *
     * @var array
     */
    protected $ignoredExtensions = ['db', 'jpg', 'png', 'txt', 'rtf', 'wma', 'wmv'];

    /**
     * Used to stop the import process when a SIGINT is received.
     *
     * @var bool
     */
    protected $isInterrupted = false;

    /**
     * A counter for the number of processed tracks.
     *
     * @var int
     */
    protected $currentFile;

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handleInterrupt($signo)
    {
        $this->error('Import aborted!');
        $this->error('Resume it from here using: --startAt=' . $this->currentFile);
        $this->isInterrupted = true;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        pcntl_signal(SIGINT, [$this, 'handleInterrupt']);

        $mlpmaPath = Config::get('ponyfm.files_directory') . '/mlpma';
        $tmpPath = Config::get('ponyfm.files_directory') . '/tmp';

        if (!File::exists($tmpPath)) {
            File::makeDirectory($tmpPath);
        }

        $UNKNOWN_GENRE = Genre::firstOrCreate([
            'name' => 'Unknown',
            'slug' => 'unknown'
        ]);

        $this->comment('Enumerating MLP Music Archive source files...');
        $files = File::allFiles($mlpmaPath);
        $this->info(sizeof($files) . ' files found!');

        $this->comment('Enumerating artists...');
        $artists = File::directories($mlpmaPath);
        $this->info(sizeof($artists) . ' artists found!');

        $this->comment('Importing tracks...');

        $totalFiles = sizeof($files);

        $fileToStartAt = (int)$this->option('startAt') - 1;
        $this->comment("Skipping $fileToStartAt files..." . PHP_EOL);

        $files = array_slice($files, $fileToStartAt);
        $this->currentFile = $fileToStartAt;

        foreach ($files as $file) {
            $this->currentFile++;

            pcntl_signal_dispatch();
            if ($this->isInterrupted) {
                break;
            }

            $this->comment('[' . $this->currentFile . '/' . $totalFiles . '] Importing track [' . $file->getFilename() . ']...');

            if (in_array($file->getExtension(), $this->ignoredExtensions)) {
                $this->comment('This is not an audio file! Skipping...' . PHP_EOL);
                continue;
            }


            // Has this track already been imported?
            $importedTrack = DB::table('mlpma_tracks')
                ->where('filename', '=', $file->getFilename())
                ->first();

            if ($importedTrack) {
                $this->comment('This track has already been imported! Skipping...' . PHP_EOL);
                continue;
            }


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

            if (Str::lower($file->getExtension()) === 'mp3') {
                list($parsedTags, $rawTags) = $this->getId3Tags($allTags);

            } elseif (Str::lower($file->getExtension()) === 'm4a') {
                list($parsedTags, $rawTags) = $this->getAtomTags($allTags);

            } elseif (Str::lower($file->getExtension()) === 'ogg') {
                list($parsedTags, $rawTags) = $this->getVorbisTags($allTags);

            } elseif (Str::lower($file->getExtension()) === 'flac') {
                list($parsedTags, $rawTags) = $this->getVorbisTags($allTags);

            } elseif (Str::lower($file->getExtension()) === 'wav') {
                list($parsedTags, $rawTags) = $this->getAtomTags($allTags);

            }


            //==========================================================================================================
            // Determine the release date.
            //==========================================================================================================
            $modifiedDate = Carbon::createFromTimeStampUTC(File::lastModified($file->getPathname()));
            $taggedYear = $parsedTags['year'];

            $this->info('Modification year: ' . $modifiedDate->year);
            $this->info('Tagged year: ' . $taggedYear);

            if ($taggedYear !== null && $modifiedDate->year === $taggedYear) {
                $releasedAt = $modifiedDate;
            } elseif ($taggedYear !== null && Str::length((string)$taggedYear) !== 4) {
                $this->error('This track\'s tagged year makes no sense! Using the track\'s last modified date...');
                $releasedAt = $modifiedDate;
            } elseif ($taggedYear !== null && $modifiedDate->year !== $taggedYear) {
                $this->error('Release years don\'t match! Using the tagged year...');
                $releasedAt = Carbon::create($taggedYear);

            } else {
                // $taggedYear is null
                $this->error('This track isn\'t tagged with its release year! Using the track\'s last modified date...');
                $releasedAt = $modifiedDate;
            }

            // This is later used by the classification/publishing script to determine the publication date.
            $parsedTags['released_at'] = $releasedAt->toDateTimeString();

            //==========================================================================================================
            // Does this track have vocals?
            //==========================================================================================================
            $isVocal = $parsedTags['lyrics'] !== null;


            //==========================================================================================================
            // Fill in the title tag if it's missing.
            //==========================================================================================================
            if (!$parsedTags['title']) {
                $parsedTags['title'] = $file->getBasename('.' . $file->getExtension());
            }


            //==========================================================================================================
            // Determine the genre.
            //==========================================================================================================
            $genreName = $parsedTags['genre'];
            $genreSlug = Str::slug($genreName);
            $this->info('Genre: ' . $genreName);

            if ($genreName && $genreSlug !== '') {
                $genre = Genre::where('name', '=', $genreName)->first();
                if ($genre) {
                    $genreId = $genre->id;

                } else {
                    $genre = new Genre();
                    $genre->name = $genreName;
                    $genre->slug = $genreSlug;
                    $genre->save();
                    $genreId = $genre->id;
                    $this->comment('Created a new genre!');
                }

            } else {
                $genreId = $UNKNOWN_GENRE->id; // "Unknown" genre ID
            }


            //==========================================================================================================
            // Determine which artist account this file belongs to using the containing directory.
            //==========================================================================================================
            $this->info('Path to file: ' . $file->getRelativePath());
            $path_components = explode(DIRECTORY_SEPARATOR, $file->getRelativePath());
            $artist_name = $path_components[0];
            $album_name = array_key_exists(1, $path_components) ? $path_components[1] : null;

            $this->info('Artist: ' . $artist_name);
            $this->info('Album: ' . $album_name);

            $artist = User::where('display_name', '=', $artist_name)->first();

            if (!$artist) {
                $artist = new User;
                $artist->display_name = $artist_name;
                $artist->email = null;
                $artist->is_archived = true;

                $artist->slug = Str::slug($artist_name);

                $slugExists = User::where('slug', '=', $artist->slug)->first();
                if ($slugExists) {
                    $this->error('Horsefeathers! The slug ' . $artist->slug . ' is already taken!');
                    $artist->slug = $artist->slug . '-' . Str::random(4);
                }

                $artist->save();
            }

            //==========================================================================================================
            // Extract the cover art, if any exists.
            //==========================================================================================================

            $this->comment('Extracting cover art!');
            $coverId = null;
            if (array_key_exists('comments', $allTags) && array_key_exists('picture', $allTags['comments'])) {
                $image = $allTags['comments']['picture'][0];

                if ($image['image_mime'] === 'image/png') {
                    $extension = 'png';

                } elseif ($image['image_mime'] === 'image/jpeg') {
                    $extension = 'jpg';

                } elseif ($image['image_mime'] === 'image/gif') {
                    $extension = 'gif';

                } else {
                    $this->error('Unknown cover art format!');
                }

                // write temporary image file
                $imageFilename = $file->getFilename() . ".cover.$extension";
                $imageFilePath = "$tmpPath/" . $imageFilename;
                File::put($imageFilePath, $image['data']);


                $imageFile = new UploadedFile($imageFilePath, $imageFilename, $image['image_mime']);

                $cover = Image::upload($imageFile, $artist);
                $coverId = $cover->id;

            } else {
                $this->comment('No cover art found!');
            }


            //==========================================================================================================
            // Is this part of an album?
            //==========================================================================================================

            $albumId = null;
            $albumName = $parsedTags['album'];

            if ($albumName !== null) {
                $album = Album::where('user_id', '=', $artist->id)
                    ->where('title', '=', $albumName)
                    ->first();

                if (!$album) {
                    $album = new Album;

                    $album->title = $albumName;
                    $album->user_id = $artist->id;
                    $album->cover_id = $coverId;

                    $album->save();
                }

                $albumId = $album->id;
            }


            //==========================================================================================================
            // Save this track.
            //==========================================================================================================
            // "Upload" the track to Pony.fm
            $this->comment('Transcoding the track!');
            Auth::loginUsingId($artist->id);

            $trackFile = new UploadedFile($file->getPathname(), $file->getFilename(), $allTags['mime_type']);
            Input::instance()->files->add(['track' => $trackFile]);

            $upload = new UploadTrackCommand(true, true);
            $result = $upload->execute();

            if ($result->didFail()) {
                $this->error(json_encode($result->getMessages(), JSON_PRETTY_PRINT));

            } else {
                // Save metadata.
                $track = Track::find($result->getResponse()['id']);

                $track->title = $parsedTags['title'];
                $track->cover_id = $coverId;
                $track->album_id = $albumId;
                $track->genre_id = $genreId;
                $track->track_number = $parsedTags['track_number'];
                $track->released_at = $releasedAt;
                $track->description = $parsedTags['comments'];
                $track->is_downloadable = true;
                $track->lyrics = $parsedTags['lyrics'];
                $track->is_vocal = $isVocal;
                $track->license_id = 2;
                $track->save();

                // If we made it to here, the track is intact! Log the import.
                DB::table('mlpma_tracks')
                    ->insert([
                        'track_id' => $result->getResponse()['id'],
                        'path' => $file->getRelativePath(),
                        'filename' => $file->getFilename(),
                        'extension' => $file->getExtension(),
                        'imported_at' => Carbon::now(),
                        'parsed_tags' => json_encode($parsedTags),
                        'raw_tags' => json_encode($rawTags),
                    ]);
            }

            echo PHP_EOL . PHP_EOL;
        }
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

        return [
            [
                'title' => isset($tags['title']) ? $tags['title'][0] : null,
                'artist' => isset($tags['artist']) ? $tags['artist'][0] : null,
                'band' => isset($tags['band']) ? $tags['band'][0] : null,
                'genre' => isset($tags['genre']) ? $tags['genre'][0] : null,
                'track_number' => isset($tags['track_number']) ? $tags['track_number'][0] : null,
                'album' => isset($tags['album']) ? $tags['album'][0] : null,
                'year' => isset($tags['year']) ? (int)$tags['year'][0] : null,
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
    protected function getAtomTags($rawTags)
    {
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
                'year' => isset($tags['year']) ? (int)$tags['year'][0] : null,
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
    protected function getVorbisTags($rawTags)
    {
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
                'year' => isset($tags['year']) ? (int)$tags['year'][0] : null,
                'comments' => isset($tags['comments']) ? $tags['comments'][0] : null,
                'lyrics' => isset($tags['lyrics']) ? $tags['lyrics'][0] : null,
            ],
            $tags
        ];
    }
}
