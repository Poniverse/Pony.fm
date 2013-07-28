<?php

use Illuminate\Database\Migrations\Migration;

class CreatePlaylists extends Migration {
	public function up() {
		Schema::create('playlists', function($table) {
			$table->increments('id');
			$table->integer('user_id')->unsigned()->index();
			$table->string('title');
			$table->string('slug');
			$table->text('description');
			$table->boolean('is_public');
			$table->timestamps();
			$table->date('deleted_at')->nullable()->index();

			$table->foreign('user_id')->references('id')->on('users')->on_update('cascade');
		});

		Schema::create('pinned_playlists', function($table) {
			$table->increments('id');
			$table->integer('user_id')->unsigned()->index();
			$table->integer('playlist_id')->unsigned()->index();
			$table->timestamps();

			$table->foreign('user_id')->references('id')->on('users')->on_update('cascade');
			$table->foreign('playlist_id')->references('id')->on('playlists')->on_update('cascade');
		});

		Schema::create('playlist_track', function($table){
			$table->increments('id');
			$table->timestamps();
			$table->integer('playlist_id')->unsigned()->index();
			$table->integer('track_id')->unsigned()->index();
			$table->integer('position')->unsigned();

			$table->foreign('playlist_id')->references('id')->on('playlists')->on_update('cascade')->on_delete('cascade');
			$table->foreign('track_id')->references('id')->on('tracks')->on_update('cascade');
		});
	}

	public function down() {
		Schema::table('playlist_track', function($table){
			$table->dropForeign('playlist_track_playlist_id_foreign');
			$table->dropForeign('playlist_track_track_id_foreign');
		});

		Schema::drop('playlist_track');

		Schema::table('pinned_playlists', function($table){
			$table->dropForeign('pinned_playlists_user_id_foreign');
			$table->dropForeign('pinned_playlists_playlist_id_foreign');
		});

		Schema::drop('pinned_playlists');

		Schema::table('playlists', function($table){
			$table->dropForeign('playlists_user_id_foreign');
		});

		Schema::drop('playlists');
	}

}