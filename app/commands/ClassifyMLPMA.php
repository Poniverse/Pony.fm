<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Entities\ShowSong;
use Entities\Track;
use Entities\TrackType;

class ClassifyMLPMA extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'mlpma:classify';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Adds Pony.fm-specific metadata to imported MLPMA tracks.';

	/**
	 * A counter for the number of processed tracks.
	 *
	 * @var int
	 */
	protected $currentTrack = 0;

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
		// Get the list of tracks that need classification
		$tracks = DB::table('mlpma_tracks')
			->orderBy('id')
			->get();

		$this->comment('Importing tracks...');

		$totalTracks = sizeof($tracks);

		$fileToStartAt = (int) $this->option('startAt') - 1;
		$this->comment("Skipping $fileToStartAt files..." . PHP_EOL);

		$tracks = array_slice($tracks, $fileToStartAt);
		$this->currentTrack = $fileToStartAt;

		foreach ($tracks as $track) {
			$this->currentTrack++;
			$this->comment('[' . $this->currentTrack . '/' . $totalTracks . '] Classifying track [' . $track->filename . ']...');

			$parsedTags = json_decode($track->parsed_tags, true);


			//==========================================================================================================
			// Original, show song remix, fan song remix, show audio remix, or ponified song?
			//==========================================================================================================
			$sanitizedTrackTitle = $parsedTags['title'];
			$sanitizedTrackTitle = str_replace(['-', '+', '~', 'ft.', '*'], ' ', $sanitizedTrackTitle);

			$queriedTitle = DB::connection()->getPdo()->quote($sanitizedTrackTitle);
			$officialSongs = ShowSong::select(['id', 'title'])
				->whereRaw("
				MATCH (title)
                AGAINST ($queriedTitle IN BOOLEAN MODE)
                ")
				->get();


			// If it has "Ingram" in the name, it's definitely an official song remix.
			if (Str::contains(Str::lower($track->filename), 'ingram')) {
				$this->info('This is an official song remix!');

				list($trackType, $linkedSongIds) = $this->classifyTrack($track->filename, $officialSongs, true);


			// If it has "remix" in the name, it's definitely a remix.
			} else if (Str::contains(Str::lower($sanitizedTrackTitle), 'remix')) {
				$this->info('This is some kind of remix!');

				list($trackType, $linkedSongIds) = $this->classifyTrack($track->filename, $officialSongs);

			// No idea what this is. Have the pony at the terminal figure it out!
			} else {
				list($trackType, $linkedSongIds) = $this->classifyTrack($track->filename, $officialSongs);
			}


			//==========================================================================================================
			// Attach the data and publish the track!
			//==========================================================================================================

			$track = Track::find($track->track_id);

			$track->track_type_id = $trackType;
			$track->published_at = $parsedTags['released_at'];
			$track->save();

			if (sizeof($linkedSongIds) > 0) {
				$track->showSongs()->attach($linkedSongIds);
			}

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
		return array(
			array('startAt', null, InputOption::VALUE_OPTIONAL, 'Track to start importing from. Useful for resuming an interrupted import.', 1),
		);
	}


	/**
	 * Determines what type of track the given file is. If unable to guess, the user
	 * is asked to identify it interactively.
	 *
	 * @param string $filename
	 * @param \Entities\ShowSong[] $officialSongs
	 * @param bool|false $isRemixOfOfficialTrack
	 * @return array
	 */
	protected function classifyTrack($filename, $officialSongs, $isRemixOfOfficialTrack = false) {
		$trackTypeId = null;
		$linkedSongIds = [];


		foreach ($officialSongs as $song) {
			$this->comment('=> Matched official song: [' . $song->id . '] ' . $song->title);
		}


		if ($isRemixOfOfficialTrack && sizeof($officialSongs) === 1) {
			$linkedSongIds = [$officialSongs[0]->id];

		} else {
			if ($isRemixOfOfficialTrack && sizeof($officialSongs) > 1) {
				$this->question('Multiple official songs matched! Please enter the ID of the correct one.');

			} else if (sizeof($officialSongs) > 0) {
				$this->question('This looks like a remix of an official song!');
				$this->question('Press "r" if the match above is right!');

			} else {
				$this->question('Exactly what kind of track is this?');

			}
			$this->question('If this is a medley, multiple song ID\'s can be separated by commas. ');
			$this->question('                                                                    ');
			$this->question('  ' . $filename . '  ');
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

}
