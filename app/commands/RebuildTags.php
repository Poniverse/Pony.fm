<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Entities\Track;

class RebuildTags extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'rebuild:tags';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Command description.';

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
		if ($this->argument('trackId')) {
			$track = Track::findOrFail($this->argument('trackId'));
			$tracks = [$track];
		} else {
			$tracks = Track::whereNotNull('published_at')->get();
		}

		foreach($tracks as $track) {
			$this->comment('Rewriting tags for track #'.$track->id.'...');
			$track->updateTags();
		}
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('trackId', InputArgument::OPTIONAL, 'ID of the track to rebuild tags for.'),
		);
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

}
