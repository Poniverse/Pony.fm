<?php

use Illuminate\Database\Migrations\Migration;

class CreateTracksTable extends Migration {
	public function up() {
		Schema::create('licenses', function($table){
			$table->increments('id');
			$table->string('title', 100);
			$table->text('description');
			$table->boolean('affiliate_distribution');
			$table->boolean('open_distribution');
			$table->boolean('remix');
		});

		Schema::create('genres', function($table){
			$table->increments('id');
			$table->string('name')->unique();
			$table->string('slug', 200)->index();
		});

		Schema::create('track_types', function($table){
			$table->increments('id');
			$table->string('title');
			$table->string('editor_title');
		});

		Schema::create('tracks', function($table){
			$table->increments('id');

			$table->integer('user_id')->unsigned();
			$table->integer('license_id')->unsigned()->nullable()->default(NULL);
			$table->integer('genre_id')->unsigned()->nullable()->index()->default(NULL);
			$table->integer('track_type_id')->unsigned()->nullable()->default(NULL);

			$table->string('title', 100)->index();
			$table->string('slug', 200)->index();
			$table->text('description')->nullable();
			$table->text('lyrics')->nullable();
			$table->boolean('is_vocal');
			$table->boolean('is_explicit');
			$table->integer('cover_id')->unsigned()->nullable();
			$table->boolean('is_downloadable');
			$table->float('duration')->unsigned();

			$table->timestamps();
			$table->timestamp('deleted_at')->nullable()->index();
			$table->timestamp('published_at')->nullable()->index();
			$table->timestamp('released_at')->nullable();

			$table->foreign('user_id')->references('id')->on('users');
			$table->foreign('license_id')->references('id')->on('licenses');
			$table->foreign('genre_id')->references('id')->on('genres')->on_update('cascade');
			$table->foreign('track_type_id')->references('id')->on('track_types')->on_update('cascade');
		});


		DB::table('licenses')->insert([
			'title'						=>	'Personal',
			'description'				=>	'Only you and Pony.fm are allowed to distribute and broadcast the track.',
			'affiliate_distribution'	=>	0,
			'open_distribution'			=>	0,
			'remix'						=>	0
		]);

		DB::table('licenses')->insert([
			'title'						=>	'Broadcast',
			'description'				=>	'You, Pony.fm, and its affiliates may distribute and broadcast the track.',
			'affiliate_distribution'	=>	1,
			'open_distribution'			=>	0,
			'remix'						=>	0
		]);

		DB::table('licenses')->insert([
			'title'						=>	'Open',
			'description'				=>	'Anyone is permitted to broadcast and distribute the song in its original form, with attribution to you.',
			'affiliate_distribution'	=>	1,
			'open_distribution'			=>	1,
			'remix'						=>	0
		]);

		DB::table('licenses')->insert([
			'title'						=>	'Remix',
			'description'				=>	'Anyone is permitted to broadcast and distribute the song in any form, or create derivative works based on it for any purpose, with attribution to you.',
			'affiliate_distribution'	=>	1,
			'open_distribution'			=>	1,
			'remix'						=>	1
		]);

		DB::table('track_types')->insert([
			'title'			=>	'Original Song',
			'editor_title'	=>	'an original song'
		]);

		DB::table('track_types')->insert([
			'title'			=>	'Official Song Remix',
			'editor_title'	=>	'a remix of an official song'
		]);

		DB::table('track_types')->insert([
			'title'			=>	'Fan Song Remix',
			'editor_title'	=>	'a remix of a fan song'
		]);

		DB::table('track_types')->insert([
			'title'			=>	'Ponified Song',
			'editor_title'	=>	'a non-pony song, turned pony'
		]);

		DB::table('track_types')->insert([
			'title'			=>	'Official Show Audio Remix',
			'editor_title'	=>	'a remix of official show audio'
		]);
	}

	public function down() {
		Schema::drop('tracks');
		Schema::drop('licenses');
		Schema::drop('track_types');
		Schema::drop('genres');
	}
}