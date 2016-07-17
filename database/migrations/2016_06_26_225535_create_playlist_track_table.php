<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePlaylistTrackTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('playlist_track', function(Blueprint $table)
		{
			$table->increments('id');
			$table->timestamps();
			$table->integer('playlist_id')->unsigned()->index();
			$table->integer('track_id')->unsigned()->index();
			$table->integer('position')->unsigned();
			$table->unique(['playlist_id','track_id']);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('playlist_track');
	}

}
