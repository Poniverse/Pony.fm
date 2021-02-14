<?php

namespace App\Console\Commands;

use App\Commands\UploadTrackCommand;
use App\Models\Album;
use App\Models\Genre;
use App\Models\Image;
use App\Models\Track;
use App\Models\User;
use Auth;
use Carbon\Carbon;
use Config;
use DB;
use File;
use getID3;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Input;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImportEQBeats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eqbeats:import
                            {--startAt=1 : Track to start importing from. Useful for resuming an interrupted import.}
                            {archiveFolder : Absolute location of archive to import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports the EQBeats archive';

    /**
     * File extensions to ignore when importing the archive.
     *
     * @var array
     */
    protected $allowedExtensions = ['mp3', 'aif', 'aiff', 'wav', 'flac', 'm4a', 'ogg'];
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
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handleInterrupt($signo)
    {
        $this->error('Import aborted!');
        $this->error('Resume it from here using: --startAt='.$this->currentFile);
        $this->isInterrupted = true;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Most of this is the same as the old ImportMLPMA.php command with a few tweaks
        // to use the new upload system and the newer version of Laravel

        pcntl_signal(SIGINT, [$this, 'handleInterrupt']);

        $archivePath = $this->argument('archiveFolder');
        $tmpPath = Config::get('ponyfm.files_directory').'/tmp';

        if (! File::exists($tmpPath)) {
            File::makeDirectory($tmpPath);
        }

        $UNKNOWN_GENRE = Genre::firstOrCreate([
            'name' => 'Unknown',
            'slug' => 'unknown',
        ]);

        //==========================================================================================================
        // Get the list of files and artists
        //==========================================================================================================
        $this->comment('Enumerating files...');
        $files = File::allFiles($archivePath);
        $this->info(count($files).' files found!');

        $this->comment('Enumerating artists...');
        $artists = File::directories($archivePath);
        $this->info(count($artists).' artists found!');

        $this->comment('Importing tracks...');

        $totalFiles = count($files);

        $fileToStartAt = (int) $this->option('startAt') - 1;
        $this->comment("Skipping $fileToStartAt files...".PHP_EOL);

        $files = array_slice($files, $fileToStartAt);
        $this->currentFile = $fileToStartAt;

        foreach ($files as $file) {
            $this->currentFile++;

            pcntl_signal_dispatch();

            if ($this->isInterrupted) {
                break;
            }

            $this->comment('['.$this->currentFile.'/'.$totalFiles.'] Importing track ['.$file->getFilename().']...');

            if (! in_array($file->getExtension(), $this->allowedExtensions)) {
                $this->comment('This is not an audio file! Skipping...'.PHP_EOL);
                continue;
            }

            $this->info('Path to file: '.$file->getRelativePath());
            $path_components = explode(DIRECTORY_SEPARATOR, $file->getRelativePath());
            $artist_name = $path_components[0];
            $album_name = array_key_exists(1, $path_components) ? $path_components[1] : null;

            $this->info('Artist: '.$artist_name);
            $this->info('Album: '.$album_name);

            //==========================================================================================================
            // Analyse the track so we can find the MIME type and album art
            //==========================================================================================================

            $getId3 = new getID3;

            // all tags read by getID3, including the cover art
            $allTags = $getId3->analyze($file->getPathname());

            // tags specific to a file format (ID3 or Atom), pre-normalization but with cover art removed
            $rawTags = [];

            // normalized tags used by Pony.fm
            $parsedTags = [];

            list($parsedTags, $rawTags) = $this->parseTags($file, $allTags);

            //$imageFilename = $file->getFilename() . ".tags.txt";
            //$imageFilePath = "$tmpPath/" . $imageFilename;
            //File::put($imageFilePath, print_r($allTags, true));

            // Parse out the hyphen in the file name
            $hyphen = ' - ';
            if (is_null($parsedTags['title'])) {
                $fileName = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                $parsedTags['title'] = substr($fileName, strpos($fileName, $hyphen) + strlen($hyphen));
            }

            $this->info('Title: '.$parsedTags['title']);

            //==========================================================================================================
            // Determine the release date.
            //==========================================================================================================
            $modifiedDate = Carbon::createFromTimeStampUTC(File::lastModified($file->getPathname()));
            $taggedYear = $parsedTags['year'];

            $this->info('Modification year: '.$modifiedDate->year);
            $this->info('Tagged year: '.$taggedYear);

            if ($taggedYear !== null && $modifiedDate->year === $taggedYear) {
                $releasedAt = $modifiedDate;
            } elseif ($taggedYear !== null && $modifiedDate->year !== $taggedYear) {
                $this->warn('Release years don\'t match! Using the tagged year...');
                $releasedAt = Carbon::create($taggedYear);
            } else {
                // $taggedYear is null
                $this->warn('This track isn\'t tagged with its release year! Using the track\'s last modified date...');
                $releasedAt = $modifiedDate;
            }

            // This is later used by the classification/publishing script to determine the publication date.
            $parsedTags['released_at'] = $releasedAt->toDateTimeString();

            //==========================================================================================================
            // Does this track have vocals?
            //==========================================================================================================
            $isVocal = $parsedTags['lyrics'] !== null;

            //==========================================================================================================
            // Determine the genre
            //==========================================================================================================
            $genreName = $parsedTags['genre'];
            $this->info('Genre: '.$genreName);

            if ($genreName) {
                $genre = Genre::withTrashed()->where('name', '=', $genreName)->first();
                if ($genre) {
                    if ($genre->deleted_at == null) {
                        $genre->restore();
                    }
                    $genreId = $genre->id;
                } else {
                    $genre = new Genre();
                    $genre->name = $genreName;
                    $genre->slug = Str::slug($genreName);
                    $genre->save();
                    $genreId = $genre->id;
                    $this->comment('Created a new genre!');
                }
            } else {
                $genreId = $UNKNOWN_GENRE->id; // "Unknown" genre ID
            }

            //==========================================================================================================
            // Check to see if we have this track already, if so, compare hashes of the two files
            //==========================================================================================================

            $artist = User::where('display_name', '=', $artist_name)->first();
            $artistId = null;

            $this->comment('Checking for duplicates');

            if ($artist) {
                $artistId = $artist->id;
            }

            $existingTrack = Track::where('title', '=', $parsedTags['title'])
                                  ->where('user_id', '=', $artistId)
                                  ->first();

            if ($existingTrack) {
                // We got one!!
                // Ok, let's not get too excited
                // First let's see if we have a matching file type

                $importFormat = $this->getFormat($file->getExtension());
                if ($importFormat == null) {
                    // No idea what this is, skip file
                    $this->comment(sprintf('Not an audio file (%s), skipping...', $importFormat));
                    continue;
                }

                $existingFile = null;

                foreach ($existingTrack->trackFiles as $trackFile) {
                    if ($trackFile->format == $importFormat) {
                        $existingFile = $trackFile;
                    }
                }

                if ($existingFile === null) {
                    // Can't find a matching format
                    // See if we have a higher quality source file
                    if (Track::$Formats[$importFormat]['is_lossless']) {
                        // Source is lossless, is the existing track lossy?
                        if ($existingFile->isMasterLossy()) {
                            // Cool! Let's replace it
                            $this->comment('Replacing ('.$existingTrack->id.') '.$existingTrack->title);

                            $this->replaceTrack($file, $existingTrack, $artist, $allTags['mime_type']);

                            continue;
                        }
                    }

                    continue;
                } else {
                    $this->comment('Found existing file');

                    // Found a matching format, are they the same?
                    // Before we check it, see if it came from MLPMA
                    // We're only replacing tracks with the same format if they're archived
                    $mlpmaTrack = DB::table('mlpma_tracks')->where('track_id', '=', $existingTrack->id)->first();

                    if (! is_null($mlpmaTrack)) {
                        $getId3_source = new getID3;

                        $getId3_source->option_md5_data = true;
                        $getId3_source->option_md5_data_source = true;

                        $sourceWithMd5 = $getId3_source->analyze($file->getPathname());

                        $getId3_existing = new getID3;
                        $getId3_existing->option_md5_data = true;
                        $getId3_existing->option_md5_data_source = true;
                        $existingFileTags = $getId3_existing->analyze($existingFile->getFile());

                        $importHash = array_key_exists('md5_data_source', $sourceWithMd5) ? $sourceWithMd5['md5_data_source'] : $sourceWithMd5['md5_data'];
                        $targetHash = array_key_exists('md5_data_source', $existingFileTags) ? $existingFileTags['md5_data_source'] : $existingFileTags['md5_data'];

                        $this->info('Archive hash: '.$importHash);
                        $this->info('Pony.fm hash: '.$targetHash);

                        if ($importHash == $targetHash) {
                            // Audio is identical, no need to reupload
                            // We can update the metadata though
                            $this->comment("Versions are the same. Updating metadata...\n");
                            $changedMetadata = false;

                            if (strlen($existingTrack->description) < strlen($parsedTags['comments'])) {
                                $existingTrack->description = $parsedTags['comments'];
                                $changedMetadata = true;
                                $this->comment('Updated description');
                            }

                            if (strlen($existingTrack->lyrics) < strlen($parsedTags['lyrics'])) {
                                $existingTrack->lyrics = $parsedTags['lyrics'];
                                $changedMetadata = true;
                                $this->comment('Updated lyrics');
                            }

                            if ($changedMetadata) {
                                $existingTrack->save();
                            }

                            continue;
                        } else {
                            // Audio is different, let's replace it
                            $this->comment('Replacing ('.$existingTrack->id.') '.$existingTrack->title);

                            $this->replaceTrack($file, $existingTrack, $artist, $allTags['mime_type']);

                            continue;
                        }
                    } else {
                        $this->comment('Not replacing, user uploaded');

                        // We can update the metadata though
                        $changedMetadata = false;

                        if (strlen($existingTrack->description) < strlen($parsedTags['comments'])) {
                            $existingTrack->description = $parsedTags['comments'];
                            $changedMetadata = true;
                            $this->comment('Updated description');
                        }

                        if (strlen($existingTrack->lyrics) < strlen($parsedTags['lyrics'])) {
                            $existingTrack->lyrics = $parsedTags['lyrics'];
                            $changedMetadata = true;
                            $this->comment('Updated lyrics');
                        }

                        if ($changedMetadata) {
                            $existingTrack->save();
                        }
                        continue;
                    }
                }
            } else {
                $this->comment('No duplicates');
            }

            //==========================================================================================================
            // Create new user for the artist if one doesn't exist
            //==========================================================================================================

            if (! $artist) {
                $artist = new User;
                $artist->display_name = $artist_name;
                $artist->email = null;
                $artist->is_archived = true;

                $artist->slug = Str::slug($artist_name);

                $slugExists = User::where('slug', '=', $artist->slug)->first();
                if ($slugExists) {
                    $this->error('Horsefeathers! The slug '.$artist->slug.' is already taken!');
                    $artist->slug = $artist->slug.'-'.Str::random(4);
                }

                $artist->save();
            }

            //==========================================================================================================
            // Grab the image and save it so we can pass that along when the track gets uploaded
            //==========================================================================================================

            $this->comment('Extracting cover art!');
            $coverId = null;
            $image = null;

            if (array_key_exists('comments', $allTags) && array_key_exists('picture', $allTags['comments'])) {
                $image = $allTags['comments']['picture'][0];
            } elseif (array_key_exists('id3v2', $allTags) && array_key_exists('APIC', $allTags['id3v2'])) {
                $image = $allTags['id3v2']['APIC'][0];
            }

            if ($image !== null) {
                if ($image['image_mime'] === 'image/png') {
                    $extension = 'png';
                } else {
                    if ($image['image_mime'] === 'image/jpeg') {
                        $extension = 'jpg';
                    } else {
                        if ($image['image_mime'] === 'image/gif') {
                            $extension = 'gif';
                        } else {
                            $this->error('Unknown cover art format!');
                        }
                    }
                }
                // write temporary image file
                $imageFilename = $file->getFilename().".cover.$extension";
                $imageFilePath = "$tmpPath/".$imageFilename;
                File::put($imageFilePath, $image['data']);

                $imageFile = new UploadedFile($imageFilePath, $imageFilename, $image['image_mime'], null, null, true);
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

                if (! $album) {
                    $album = new Album;

                    $album->title = $albumName;
                    $album->user_id = $artist->id;
                    $album->cover_id = $coverId;
                    $album->description = '';

                    $album->save();
                }

                $albumId = $album->id;
            }

            //==========================================================================================================
            // Send the track into the upload system like a user just uploaded a track
            //==========================================================================================================

            $this->comment('Transcoding the track!');
            Auth::loginUsingId($artist->id);

            $mime = $allTags['mime_type'];

            File::copy($file->getPathname(), "$tmpPath/".$file->getFilename());
            $trackFile = new UploadedFile("$tmpPath/".$file->getFilename(), $file->getFilename(), $mime, null, null, true);

            $upload = new UploadTrackCommand(true);
            $upload->_file = $trackFile;
            $result = $upload->execute();

            if ($result->didFail()) {
                $this->error(json_encode($result->getValidator()->messages()->getMessages(), JSON_PRETTY_PRINT));
            } else {
                $track = Track::find($result->getResponse()['id']);
                $track->cover_id = $coverId;
                $track->album_id = $albumId;
                $track->genre_id = $genreId;
                $track->track_number = $parsedTags['track_number'];
                $track->released_at = $releasedAt;
                $track->is_downloadable = true;
                $track->is_vocal = $isVocal;
                $track->license_id = 2;
                $track->description = '';
                $track->lyrics = '';

                if (! is_null($parsedTags['comments'])) {
                    $track->description = $parsedTags['comments'];
                }

                if (! is_null($parsedTags['lyrics'])) {
                    $track->lyrics = $parsedTags['lyrics'];
                }

                if (! is_null($parsedTags['title'])) {
                    $track->title = $parsedTags['title'];
                }

                $track->save();

                // If we made it to here, the track is intact! Log the import.
                DB::table('eqbeats_tracks')
                    ->insert([
                        'track_id' => $result->getResponse()['id'],
                        'path' => $file->getRelativePath(),
                        'filename' => iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $file->getFilename()),
                        'extension' => $file->getExtension(),
                        'imported_at' => Carbon::now(),
                        'parsed_tags' => json_encode($parsedTags),
                        'raw_tags' => json_encode($rawTags),
                    ]);
            }

            echo PHP_EOL.PHP_EOL;
        }
    }

    protected function hashAudio($filepath)
    {
        $hash = hash_file('crc32b', $filepath);
        $array = unpack('N', pack('H*', $hash));

        return $array[1];
    }

    protected function getFormat($extension)
    {
        foreach (Track::$Formats as $name => $format) {
            if ($format['extension'] == $extension) {
                return $name;
            }
        }

        return null;
    }

    public function parseTags($file, $allTags)
    {
        $audioCodec = $file->getExtension();

        //==========================================================================================================
        // Extract the original tags.
        //==========================================================================================================

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

        return [$parsedTags, $rawTags];
    }

    /**
     * @param array $rawTags
     * @return array
     */
    protected function getId3Tags($rawTags)
    {
        $tags = [];

        if (array_key_exists('tags', $rawTags)) {
            if (array_key_exists('id3v2', $rawTags['tags'])) {
                $tags = $rawTags['tags']['id3v2'];
            } elseif (array_key_exists('id3v1', $rawTags['tags'])) {
                $tags = $rawTags['tags']['id3v1'];
            } else {
                $tags = [];
            }
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

    protected function replaceTrack($toBeUploaded, $targetTrack, $artist, $mime)
    {
        Auth::loginUsingId($artist->id);

        $trackFile = new UploadedFile($toBeUploaded->getPathname(), $toBeUploaded->getFilename(), $mime, null, null, true);

        $upload = new UploadTrackCommand(true, false, null, false, $targetTrack->getNextVersion(), $targetTrack);
        $upload->_file = $trackFile;
        $result = $upload->execute();

        if ($result->didFail()) {
            $this->error(json_encode($result->getValidator()->messages()->getMessages(), JSON_PRETTY_PRINT));
        } else {
            $track = Track::find($result->getResponse()['id']);
            $track->license_id = 2;
            $track->save();
        }
    }
}
