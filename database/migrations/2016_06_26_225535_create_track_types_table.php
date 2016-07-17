<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTrackTypesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('track_types', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('title');
			$table->string('editor_title');
		});

        DB::table('track_types')->insert([
            'id' => 1,
            'title' => 'Original Song',
            'editor_title' => 'an original song'
        ]);

        DB::table('track_types')->insert([
            'id' => 2,
            'title' => 'Official Song Remix',
            'editor_title' => 'a remix of an official song'
        ]);

        DB::table('track_types')->insert([
            'id' => 3,
            'title' => 'Fan Song Remix',
            'editor_title' => 'a remix of a fan song'
        ]);

        DB::table('track_types')->insert([
            'id' => 4,
            'title' => 'Ponified Song',
            'editor_title' => 'a non-pony song, turned pony'
        ]);

        DB::table('track_types')->insert([
            'id' => 5,
            'title' => 'Official Show Audio Remix',
            'editor_title' => 'a remix of official show audio'
        ]);

        DB::table('track_types')->insert([
            'id' => 6,
            'title' => 'Unclassified',
            'editor_title' => 'an unclassified track'
        ]);
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('track_types');
	}

}
