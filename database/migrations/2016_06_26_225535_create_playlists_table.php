<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePlaylistsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('playlists', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('user_id')->unsigned()->index();
			$table->string('title')->index();
			$table->string('slug');
			$table->text('description', 65535);
			$table->boolean('is_public')->index();
			$table->integer('track_count')->unsigned()->index();
			$table->integer('view_count')->unsigned();
			$table->integer('download_count')->unsigned();
			$table->integer('favourite_count')->unsigned();
			$table->integer('follow_count')->unsigned();
			$table->integer('comment_count')->unsigned();
			$table->timestamps();
			$table->date('deleted_at')->nullable()->index();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('playlists');
	}

}
