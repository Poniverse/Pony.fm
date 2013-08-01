<?php

use Illuminate\Database\Migrations\Migration;

class CreateFavourites extends Migration {
	public function up() {
		Schema::create('favourites', function($table){
			$table->increments('id');
			$table->integer('user_id')->unsigned()->index();

			$table->integer('track_id')->unsigned()->nullable()->index();
			$table->integer('album_id')->unsigned()->nullable()->index();
			$table->integer('playlist_id')->unsigned()->nullable()->index();

			$table->timestamps();

			$table->foreign('user_id')->references('id')->on('users')->on_delete('cascade');
			$table->foreign('track_id')->references('id')->on('tracks');
			$table->foreign('album_id')->references('id')->on('albums');
			$table->foreign('playlist_id')->references('id')->on('playlists');
		});
	}

	public function down() {
		Schema::table('favourites', function($table){
			$table->dropForeign('favourites_user_id_foreign');
			$table->dropForeign('favourites_track_id_foreign');
			$table->dropForeign('favourites_album_id_foreign');
			$table->dropForeign('favourites_playlist_id_foreign');
		});

		Schema::drop('favourites');
	}
}