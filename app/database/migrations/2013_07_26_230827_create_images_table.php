<?php

use Illuminate\Database\Migrations\Migration;

class CreateImagesTable extends Migration {
	public function up() {
		Schema::create('images', function($table) {
			$table->increments('id');
			$table->string('filename', 256);
			$table->string('mime', 100);
			$table->string('extension', 32);
			$table->integer('size');
			$table->string('hash', 32);
			$table->integer('uploaded_by')->unsigned();
			$table->timestamps();

			$table->foreign('uploaded_by')->references('id')->on('users');
		});

		Schema::table('users', function($table) {
			$table->integer('avatar_id')->unsigned()->nullable();
			$table->foreign('avatar_id')->references('id')->on('images');
		});

		DB::table('tracks')->update(['cover_id' => null]);

		Schema::table('tracks', function($table) {
			$table->foreign('cover_id')->references('id')->on('images');
		});
	}

	public function down() {
		Schema::table('tracks', function($table) {
			$table->dropForeign('tracks_cover_id_foreign');
		});

		Schema::table('users', function($table) {
			$table->dropForeign('users_avatar_id_foreign');
			$table->dropColumn('avatar_id');
		});

		Schema::drop('images');
	}
}