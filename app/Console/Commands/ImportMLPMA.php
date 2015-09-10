<?php

namespace App\Console\Commands;

use App\Image;
use App\Track;
use App\User;
use Auth;
use Carbon\Carbon;
use Config;
use DB;
use File;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Input;
use Symfony\Component\HttpFoundation\File\UploadedFile;

//require_once(app_path() . '/Library/getid3/getid3/getid3.php');

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
    protected $ignoredExtensions = ['db', 'jpg', 'png'];

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

        $mlpmaPath = Config::get('app.files_directory') . 'mlpma';
        $tmpPath = Config::get('app.files_directory') . 'tmp';

        if (!File::exists($tmpPath)) {
            File::makeDirectory($tmpPath);
        }

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

            if ($file->getExtension() === 'mp3') {
                list($parsedTags, $rawTags) = $this->getId3Tags($allTags);

            } else {
                if ($file->getExtension() === 'm4a') {
                    list($parsedTags, $rawTags) = $this->getAtomTags($allTags);
                }
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

            } else {
                if ($taggedYear !== null && $modifiedDate->year !== $taggedYear) {
                    $this->error('Release years don\'t match! Using the tagged year...');
                    $releasedAt = Carbon::create($taggedYear);

                } else {
                    // $taggedYear is null
                    $this->error('This track isn\'t tagged with its release year! Using the track\'s last modified date...');
                    $releasedAt = $modifiedDate;
                }
            }

            // This is later used by the classification/publishing script to determine the publication date.
            $parsedTags['released_at'] = $releasedAt->toDateTimeString();

            //==========================================================================================================
            // Does this track have vocals?
            //==========================================================================================================
            $isVocal = $parsedTags['lyrics'] !== null;


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
            // Find the album if it exists and create it if it doesn't.
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

            $upload = new UploadTrackCommand(true);
            $result = $upload->execute();

            if ($result->didFail()) {
                $this->error(json_encode($result->getValidator()->messages()->getMessages(), JSON_PRETTY_PRINT));

            } else {
                // Save metadata.
                $track = Track::find($result->getResponse()['id']);

                $track->title = $parsedTags['title'];
                $track->cover_id = $coverId;
                $track->album_id = $albumId;
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
        $tags = $rawTags['tags']['id3v2'];
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
                'title' => $tags['title'][0],
                'artist' => $tags['artist'][0],
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
        $tags = $rawTags['tags']['quicktime'];

        $trackNumber = null;
        if (isset($tags['track_number'])) {
            $trackNumberComponents = explode('/', $tags['track_number'][0]);
            $trackNumber = $trackNumberComponents[0];
        }

        return [
            [
                'title' => $tags['title'][0],
                'artist' => $tags['artist'][0],
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
