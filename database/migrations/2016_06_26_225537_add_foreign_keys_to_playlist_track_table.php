<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToPlaylistTrackTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('playlist_track', function(Blueprint $table)
		{
			$table->foreign('playlist_id')->references('id')->on('playlists')->onUpdate('RESTRICT')->onDelete('RESTRICT');
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
		Schema::table('playlist_track', function(Blueprint $table)
		{
			$table->dropForeign('playlist_track_playlist_id_foreign');
			$table->dropForeign('playlist_track_track_id_foreign');
		});
	}

}
