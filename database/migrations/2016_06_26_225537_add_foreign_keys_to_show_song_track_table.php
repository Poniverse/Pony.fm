<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToShowSongTrackTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('show_song_track', function(Blueprint $table)
		{
			$table->foreign('show_song_id')->references('id')->on('show_songs')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('track_id')->references('id')->on('tracks')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('show_song_track', function(Blueprint $table)
		{
			$table->dropForeign('show_song_track_show_song_id_foreign');
			$table->dropForeign('show_song_track_track_id_foreign');
		});
	}

}
