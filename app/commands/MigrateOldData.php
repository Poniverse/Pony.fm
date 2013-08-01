<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class MigrateOldData extends Command {
	protected $name = 'migrate-old-data';
	protected $description = 'Migrates data from the old pfm site.';

	public function __construct()
	{
		parent::__construct();
	}

	public function fire() {
		$this->call('migrate:refresh');

		$oldDb = DB::connection('old');
		$oldUsers = $oldDb->table('users')->get();

		foreach ($oldUsers as $user) {
			$displayName = $user->display_name;
			if (!$displayName)
				$displayName = $user->username;

			if (!$displayName)
				$displayName = $user->mlpforums_name;

			if (!$displayName)
				continue;

			DB::table('users')->insert([
				'id' => $user->id,
				'display_name' => $displayName,
				'email' => $user->email,
				'created_at' => $user->created_at,
				'updated_at' => $user->updated_at,
				'slug' => $user->slug,
				'password_hash' => $user->password_hash,
				'password_salt' => $user->password_salt,
				'bio' => $user->bio,
				'sync_names' => $user->sync_names,
				'can_see_explicit_content' => $user->can_see_explicit_content,
				'mlpforums_name' => $user->mlpforums_name,
				'uses_gravatar' => $user->uses_gravatar,
				'gravatar' => $user->gravatar
			]);
		}
	}

	protected function getArguments()
	{
		return [
			['example', InputArgument::REQUIRED, 'An example argument.'],
		];
	}

	protected function getOptions() {
		return [
			['example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null],
		];
	}
}