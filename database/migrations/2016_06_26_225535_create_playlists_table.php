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
			$table->unsignedTinyInteger('is_public')->index();
			$table->integer('track_count')->unsigned()->default(0)->index();
			$table->integer('view_count')->unsigned()->default(0);
			$table->integer('download_count')->unsigned()->default(0);
			$table->integer('favourite_count')->unsigned()->default(0);
			$table->integer('follow_count')->unsigned()->default(0);
			$table->integer('comment_count')->unsigned()->default(0);
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
