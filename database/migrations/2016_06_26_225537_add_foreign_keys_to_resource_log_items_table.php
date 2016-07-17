<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToResourceLogItemsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('resource_log_items', function(Blueprint $table)
		{
			$table->foreign('album_id')->references('id')->on('albums')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('playlist_id')->references('id')->on('playlists')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('track_id')->references('id')->on('tracks')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('user_id')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('resource_log_items', function(Blueprint $table)
		{
			$table->dropForeign('resource_log_items_album_id_foreign');
			$table->dropForeign('resource_log_items_playlist_id_foreign');
			$table->dropForeign('resource_log_items_track_id_foreign');
			$table->dropForeign('resource_log_items_user_id_foreign');
		});
	}

}
