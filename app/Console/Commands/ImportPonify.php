<?php

namespace Poniverse\Ponyfm\Console\Commands;

use Auth;
use Config;
use DB;
use File;
use Input;
use getID3;
use Poniverse\Ponyfm\Models\Image;
use Poniverse\Ponyfm\Models\Track;
use Poniverse\Ponyfm\Models\User;
use Poniverse\Ponyfm\Commands\UploadTrackCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImportPonify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ponify:import
                            {--startAt=1 : Track to start importing from. Useful for resuming an interrupted import.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports the Ponify archive';

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
     * @return void
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
     * @return mixed
     */
    public function handle()
    {
        // Most of this is the same as the old ImportMLPMA.php command with a few tweaks
        // to use the new upload system and the newer version of Laravel

        pcntl_signal(SIGINT, [$this, 'handleInterrupt']);

        $ponifyPath = Config::get('ponyfm.files_directory').'/ponify';
        $tmpPath = Config::get('ponyfm.files_directory').'/tmp';

        if (!File::exists($tmpPath)) {
            File::makeDirectory($tmpPath);
        }

        //==========================================================================================================
        // Get the list of files and artists
        //==========================================================================================================
        $this->comment('Enumerating Ponify files...');
        $files = File::allFiles($ponifyPath);
        $this->info(sizeof($files) . ' files found!');

        $this->comment('Enumerating artists...');
        $artists = File::directories($ponifyPath);
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

            $this->info('Path to file: ' . $file->getRelativePath());
            $path_components = explode(DIRECTORY_SEPARATOR, $file->getRelativePath());
            $artist_name = $path_components[0];
            $album_name = array_key_exists(1, $path_components) ? $path_components[1] : null;

            $this->info('Artist: ' . $artist_name);
            $this->info('Album: ' . $album_name);

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

            if ($file->getExtension() === 'mp3') {
                list($parsedTags, $rawTags) = $this->getId3Tags($allTags);
            } else {
                if ($file->getExtension() === 'm4a') {
                    list($parsedTags, $rawTags) = $this->getAtomTags($allTags);
                }
            }

            //==========================================================================================================
            // Create new user for the artist if one doesn't exist
            //==========================================================================================================

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
            // Grab the image and save it so we can pass that along when the track gets uploaded
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

                $imageFile = new UploadedFile($imageFilePath, $imageFilename, $image['image_mime'], null, null, true);
                $cover = Image::upload($imageFile, $artist);
                $coverId = $cover->id;
            } else {
                $this->comment('No cover art found!');
            }

            //==========================================================================================================
            // Send the track into the upload system like a user just uploaded a track
            //==========================================================================================================

            $this->comment('Transcoding the track!');
            Auth::loginUsingId($artist->id);

            $getID3 = new getID3;
            $getID3->analyze($file->getPathname());

            $mime = null;

            if (isset($getID3->info['mime_type'])) $mime = $getID3->info['mime_type'];

            $trackFile = new UploadedFile($file->getPathname(), $file->getFilename(), $mime, null, null, true);

            $upload = new UploadTrackCommand(true);
            $upload->_file = $trackFile;
            $result = $upload->execute();

            if ($result->didFail()) {
                $this->error(json_encode($result->getValidator()->messages()->getMessages(), JSON_PRETTY_PRINT));
            } else {
                $track = Track::find($result->getResponse()['id']);
                $track->license_id = 2;
                $track->save();
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
