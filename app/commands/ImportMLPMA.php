<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Support\Facades\File;
use Entities\Image;
use Entities\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Carbon\Carbon;

require_once(app_path() . '/library/getid3/getid3/getid3.php');


class ImportMLPMA extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'import-mlpma';

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
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$mlpmaPath = Config::get('app.files_directory').'mlpma';
		$tmpPath = Config::get('app.files_directory').'tmp';

		if (!File::exists($tmpPath)) {
			File::makeDirectory($tmpPath);
		}

		$this->comment('Enumerating MLP Music Archive source files...');
		$files = File::allFiles($mlpmaPath);
		$this->info(sizeof($files).' files found!');

		$this->comment('Enumerating artists...');
		$artists = File::directories($mlpmaPath);
		$this->info(sizeof($artists).' artists found!');

		$this->comment('Importing tracks...'.PHP_EOL);

		foreach($files as $file) {
			$this->comment('Importing track ['. $file->getFilename() .']...');

			if (in_array($file->getExtension(), $this->ignoredExtensions)) {
				$this->comment('This is not an audio file! Skipping...'.PHP_EOL);
				continue;
			}


			//==========================================================================================================
			// Extract the original tags.
			//==========================================================================================================
			$getId3 = new getID3;
			$tags = $getId3->analyze($file->getPathname());

			$parsedTags = [];
			if ($file->getExtension() === 'mp3') {
				$parsedTags = $this->getId3Tags($tags);

			} else if ($file->getExtension() === 'm4a') {
				$parsedTags = $this->getAtomTags($tags);
			}


			//==========================================================================================================
			// Determine the release date.
			//==========================================================================================================
			$modifiedDate = Carbon::createFromTimeStampUTC(File::lastModified($file->getPathname()));
			$taggedYear = $parsedTags['year'];

			$this->info('Modification year: '.$modifiedDate->year);
			$this->info('Tagged year: '.$taggedYear);

			if ($taggedYear !== null && $modifiedDate->year === $taggedYear) {
				$released_at = $modifiedDate;

			} else if ($taggedYear !== null && $modifiedDate->year !== $taggedYear) {
				$this->error('Release years do not match! Using the tagged year...');
				$released_at = Carbon::create($taggedYear);

			} else {
				// $taggedYear is null
				$this->error('This track isn\'t tagged with its release year! Using the track\'s last modified date...');
				$released_at = $modifiedDate;
			}

			//==========================================================================================================
			// Does this track have vocals?
			//==========================================================================================================
			$is_vocal = $parsedTags['lyrics'] !== null;


			//==========================================================================================================
			// Determine which artist account this file belongs to using the containing directory.
			//==========================================================================================================
			$this->info('Path to file: '.$file->getRelativePath());
			$path_components = explode(DIRECTORY_SEPARATOR, $file->getRelativePath());
			$artist_name = $path_components[0];
			$album_name = array_key_exists(1, $path_components) ? $path_components[1] : null;

			$this->info('Artist: '.$artist_name);
			$this->info('Album: '.$album_name);

			$artist = User::where('display_name', '=', $artist_name)->first();

			if (!$artist) {
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

				$artist = $artist->save();
			}

			//==========================================================================================================
			// Extract the cover art, if any exists.
			//==========================================================================================================
			$cover_id = null;
			if (array_key_exists('comments', $tags) && array_key_exists('picture', $tags['comments'])) {
				$image = $tags['comments']['picture'][0];

				if ($image['image_mime'] === 'image/png') {
					$extension = 'png';

				} else if ($image['image_mime'] === 'image/jpeg') {
					$extension = 'jpg';

				} else if ($image['image_mime'] === 'image/gif') {
					$extension = 'gif';

				} else {
					$this->error('Unknown cover art format!');
				}

				// write temporary image file
				$imageFilename = $file->getFilename() . ".cover.$extension";
				$imageFilePath = "$tmpPath/".$imageFilename;
				File::put($imageFilePath, $image['data']);


				$imageFile = new UploadedFile($imageFilePath, $imageFilename, $image['image_mime']);

				$cover_id = Image::upload($imageFile, $artist);

			} else {
				$this->error('No cover art found!');
			}


			//==========================================================================================================
			// Is this part of an album?
			//==========================================================================================================

			// TODO: find/create the album


			//==========================================================================================================
			// Original, show song remix, fan song remix, show audio remix, or ponified song?
			//==========================================================================================================

			// TODO: implement this

			//==========================================================================================================
			// Save this track.
			//==========================================================================================================

			// TODO: use these variables
			$cover_id;
			$released_at;
			$is_vocal;

			echo PHP_EOL;
		}
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array();
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array();
	}


	/**
	 * @param array $rawTags
	 * @return array
	 */
	protected function getId3Tags($rawTags) {
		$tags = $rawTags['tags']['id3v2'];

		return [
			'title' => $tags['title'][0],
			'artist' => $tags['artist'][0],
			'band'  => isset($tags['band']) ? $tags['band'][0] : null,
			'genre' => isset($tags['genre']) ? $tags['genre'][0] : null,
			'track_number' => isset($tags['track_number']) ? $tags['track_number'][0] : null,
			'album' => isset($tags['album']) ? $tags['album'][0] : null,
			'year'  => isset($tags['year']) ? (int) $tags['year'][0] : null,
			'comments' => isset($tags['comments']) ? $tags['comments'][0] : null,
			'lyrics' => isset($tags['unsynchronised_lyric']) ? $tags['unsynchronised_lyric'][0] : null,
		];
	}

	/**
	 * @param array $rawTags
	 * @return array
	 */
	protected function getAtomTags($rawTags) {
		// TODO: finish this
		print_r($rawTags['tags']['quicktime']);
		print_r($rawTags['quicktime']['comments']);

		return [
			'title' => null,
			'artist' => null,
			'band' => null,
			'genre' => null,
			'track_number' => null,
			'album' => null,
			'year' => null,
			'comments' => null,
			'lyrics' => null,
		];
	}

}
