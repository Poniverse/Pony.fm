<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Support\Facades\File;
use Entities\Album;
use Entities\Image;
use Entities\User;
use Entities\ShowSong;
use Entities\Track;
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
				$releasedAt = $modifiedDate;

			} else if ($taggedYear !== null && $modifiedDate->year !== $taggedYear) {
				$this->error('Release years don\'t match! Using the tagged year...');
				$releasedAt = Carbon::create($taggedYear);

			} else {
				// $taggedYear is null
				$this->error('This track isn\'t tagged with its release year! Using the track\'s last modified date...');
				$releasedAt = $modifiedDate;
			}

			//==========================================================================================================
			// Does this track have vocals?
			//==========================================================================================================
			$isVocal = $parsedTags['lyrics'] !== null;


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
			$coverId = null;
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
				$coverId = $cover->id;

			} else {
				$this->error('No cover art found!');
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
			// Original, show song remix, fan song remix, show audio remix, or ponified song?
			//==========================================================================================================
			$trackType = TrackType::ORIGINAL_TRACK;
			$linkedSongIds = [];

			$sanitizedTrackTitle = $parsedTags['title'];
			$sanitizedTrackTitle = str_replace(' - ', ' ', $sanitizedTrackTitle);
			$sanitizedTrackTitle = str_replace('ft. ', '', $sanitizedTrackTitle);
			$sanitizedTrackTitle = str_replace('*', '', $sanitizedTrackTitle);

			$queriedTitle = DB::connection()->getPdo()->quote($sanitizedTrackTitle);
			$officialSongs = ShowSong::select(['id', 'title'])
			->whereRaw("
				MATCH (title)
                AGAINST ($queriedTitle IN BOOLEAN MODE)
                ")
				->get();


			// If it has "Ingram" in the name, it's definitely an official song remix.
			if (Str::contains(Str::lower($file->getFilename()), 'ingram')) {
				$this->comment('This is an official song remix!');

				list($trackType, $linkedSongIds) = $this->classifyTrack($file, $officialSongs, true);


			// If it has "remix" in the name, it's definitely a remix.
			} else if (Str::contains(Str::lower($sanitizedTrackTitle), 'remix')) {
				$this->comment('This is some kind of remix!');

				list($trackType, $linkedSongIds) = $this->classifyTrack($file, $officialSongs);
			}


			//==========================================================================================================
			// Save this track.
			//==========================================================================================================
			$title = $parsedTags['title'];

			// Has this track already been imported?
			$track = Track::where('user_id', '=', $artist->id)
				->where('title', '=', $title)
				->first();

			if (!$track) {
				$track = new Track;

				$track->user_id = $artist->id;
				$track->title = $parsedTags['title'];
				$track->cover_id = $coverId;
				$track->album_id = $albumId;
				$track->released_at = $releasedAt;
				$track->is_vocal = $isVocal;
				$track->track_type_id = $trackType;

				$track->save();

				if (sizeof($linkedSongIds) > 0) {
					$track->showSongs()->attach($linkedSongIds);
				}

				// TODO: mark imported tracks as needing QA
			} else {
				$this->comment('This track has already been imported!');
			}



			echo PHP_EOL.PHP_EOL;
		}
	}

	protected function classifyTrack($file, $officialSongs, $isRemixOfOfficialTrack = false)
	{
		$trackTypeId = null;
		$linkedSongIds = [];


		foreach ($officialSongs as $song) {
			$this->comment('=> Matched official song: [' . $song->id . '] ' . $song->title);
		}

		if ($isRemixOfOfficialTrack && sizeof($officialSongs) === 1) {
			$linkedSongIds = [$officialSongs[0]->id];

		} else {
			if ($isRemixOfOfficialTrack) {
				$this->question('Multiple official songs matched! Please enter the ID of the correct one.');

			} else if (sizeof($officialSongs) > 0) {
				$this->question('This looks like a remix of an official song!');
				$this->question('Press "r" if the match above is right!');

			} else {
				$this->question('Exactly what kind of track is this?');

			}
			$this->question('If this is a medley, multiple song ID\'s can be separated by commas. ');
			$this->question('                                                                    ');
			$this->question('  '.$file->getFilename().'  ');
			$this->question('                                                                    ');
			$this->question('    r = official song remix (accept all "guessed" matches)          ');
			$this->question('    # = official song remix (enter the ID(s) of the show song(s))   ');
			$this->question('    a = show audio remix                                            ');
			$this->question('    f = fan track remix                                             ');
			$this->question('    p = ponified track                                              ');
			$this->question('    o = original track                                              ');
			$this->question('                                                                    ');
			$input = $this->ask('[r/#/a/f/p/o]: ');

			switch ($input) {
				case 'r':
					$trackTypeId = TrackType::OFFICIAL_TRACK_REMIX;
					foreach ($officialSongs as $officialSong) {
						$linkedSongIds[] = (int) $officialSong->id;
					}
					break;

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
					$linkedSongIds = array_map(function ($item) {
						return (int) $item;
					}, $linkedSongIds);
			}
		}

		return [$trackTypeId, $linkedSongIds];
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
		$tags = $rawTags['tags']['quicktime'];

		$trackNumber = null;
		if (isset($tags['track_number'])) {
			$trackNumberComponents = explode('/', $tags['track_number'][0]);
			$trackNumber = $trackNumberComponents[0];
		}

		return [
			'title' => $tags['title'][0],
			'artist' => $tags['artist'][0],
			'band' => isset($tags['band']) ? $tags['band'][0] : null,
			'album_artist' => isset($tags['album_artist']) ? $tags['album_artist'][0] : null,
			'genre' => isset($tags['genre']) ? $tags['genre'][0] : null,
			'track_number' => $trackNumber,
			'album' => isset($tags['album']) ? $tags['album'][0] : null,
			'year' => isset($tags['year']) ? (int) $tags['year'][0] : null,
			'comments' => isset($tags['comments']) ? $tags['comments'][0] : null,
			'lyrics' => isset($tags['lyrics']) ? $tags['lyrics'][0] : null,
		];
	}

}
