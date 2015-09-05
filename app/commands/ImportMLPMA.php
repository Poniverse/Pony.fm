<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Support\Facades\File;
use Entities\Album;
use Entities\Image;
use Entities\User;
use Entities\ShowSong;
use Entities\TrackType;
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

		$totalFiles = sizeof($files);
		$currentFile = 0;

		foreach($files as $file) {
			$currentFile++;
			$this->comment('['.$currentFile.'/'.$totalFiles.'] Importing track ['. $file->getFilename() .']...');

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
				$this->error('Release years don\'t match! Using the tagged year...');
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

				$cover = Image::upload($imageFile, $artist);

			} else {
				$this->error('No cover art found!');
			}


			//==========================================================================================================
			// Is this part of an album?
			//==========================================================================================================

			// TODO: find/create the album
			$album_name = $parsedTags['album'];

			if ($album_name !== null) {
				$album = Album::where('user_id', '=', $artist->id)
					->where('title', '=', $album_name)
					->first();

				if (!$album) {
					$album = new Album;

					$album->title = $album_name;
					$album->user_id = $artist->id;
					$album->cover_id = $cover->id;

					$album->save();
				}
			}

			//==========================================================================================================
			// Original, show song remix, fan song remix, show audio remix, or ponified song?
			//==========================================================================================================
			$track_type = TrackType::ORIGINAL_TRACK;

			$sanitized_track_title = $parsedTags['title'];
			$sanitized_track_title = str_replace(' - ', ' ', $sanitized_track_title);
			$sanitized_track_title = str_replace('ft. ', '', $sanitized_track_title);
			$sanitized_track_title = str_replace('*', '', $sanitized_track_title);

			$queriedTitle = DB::connection()->getPdo()->quote($sanitized_track_title);
			$officialSongs = ShowSong::select(['id', 'title'])
			->whereRaw("
				MATCH (title)
                AGAINST ($queriedTitle IN BOOLEAN MODE)
                ")
				->get();


			// It it has "Ingram" in the name, it's definitely an official song remix.
			if (Str::contains(Str::lower($file->getFilename()), 'ingram')) {
				$track_type = TrackType::OFFICIAL_TRACK_REMIX;
				$this->comment('This is an official song remix!');

				foreach($officialSongs as $song) {
					$this->comment('=> Matched official song: ['.$song->id.'] '.$song->title);
				}

				if (sizeof($officialSongs) === 1) {
					$linkedSongIds = [$officialSongs[0]->id];
				} else {
					list($track_type, $linkedSongIds) = $this->classifyTrack($officialSongs);

				}


			// If it has "remix" in the name, it's definitely a remix.
			} else if (Str::contains(Str::lower($sanitized_track_title), 'remix')) {
				$this->comment('This is some kind of remix!');

				foreach($officialSongs as $song) {
					$this->comment('=> Matched official song: [' . $song->id . '] ' . $song->title);
				}
			}


			//==========================================================================================================
			// Save this track.
			//==========================================================================================================

			// TODO: use these variables
			$cover;
			$album;
			$cover_id;
			$released_at;
			$is_vocal;
			$track_type;

			// TODO: mark imported tracks as needing QA

			echo PHP_EOL;
		}
	}

	protected function classifyTrack()
	{
		$trackTypeId = null;
		$linkedSongIds = [];

		$this->question('Multiple official songs matched! Please enter the ID of the correct one.');
		$this->question('If this is a medley, multiple song ID\'s can be separated by commas.');
		$this->question('  Other options:');
		$this->question('    a = show audio remix');
		$this->question('    f = fan track remix');
		$this->question('    p = ponified track');
		$this->question('    o = original track');
		$input = $this->ask('[#/a/f/p/o]: ');

		switch($input) {
			case 'a':
				$trackTypeId = TrackType::OFFICIAL_AUDIO_REMIX;
				break;

			case 'f':
				$trackTypeId = TrackType::FAN_TRACK_REMIX;
				break;

			case 'p':
				$trackTypeId = TrackType::PONIFIED_TRACK;
				break;

			case 'o':
				$trackTypeId = TrackType::ORIGINAL_TRACK;
				break;

			default:
				$trackTypeId = TrackType::OFFICIAL_TRACK_REMIX;
				$linkedSongIds = explode(',', $input);
				$linkedSongIds = array_map(function($item){ return (int) $item;	}, $linkedSongIds);
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
