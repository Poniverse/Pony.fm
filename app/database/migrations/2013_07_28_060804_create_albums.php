<?php

use Illuminate\Database\Migrations\Migration;

class CreateAlbums extends Migration {
	public function up() {
		Schema::create('albums', function($table) {
			$table->increments('id');
			$table->integer('user_id')->unsigned();
			$table->string('title')->index();
			$table->string('slug')->index();
			$table->text('description');
			$table->integer('cover_id')->unsigned()->nullable();

			$table->integer('view_count')->unsigned();
			$table->integer('download_count')->unsigned();
			$table->integer('favourite_count')->unsigned();
			$table->integer('comment_count')->unsigned();

			$table->timestamps();
			$table->timestamp('deleted_at')->nullable()->index();

			$table->foreign('cover_id')->references('id')->on('images');
			$table->foreign('user_id')->references('id')->on('users');
		});

		Schema::table('tracks', function($table) {
			$table->integer('album_id')->unsigned()->nullable();
			$table->integer('track_number')->unsigned()->nullable();

			$table->foreign('album_id')->references('id')->on('albums');
		});
	}

	public function down() {
		Schema::table('tracks', function($table) {
			$table->dropForeign('tracks_album_id_foreign');
			$table->dropColumn('album_id');
			$table->dropColumn('track_number');
		});

		Schema::drop('albums');
	}
}