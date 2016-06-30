<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTracksTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('tracks', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('user_id')->unsigned()->index('tracks_user_id_foreign');
			$table->integer('license_id')->unsigned()->nullable()->index('tracks_license_id_foreign');
			$table->integer('genre_id')->unsigned()->nullable()->index();
			$table->integer('track_type_id')->unsigned()->nullable()->index('tracks_track_type_id_foreign');
			$table->string('title', 100)->index();
			$table->string('slug', 200)->index();
			$table->text('description', 65535)->nullable();
			$table->text('lyrics', 65535)->nullable();
			$table->boolean('is_vocal')->default(0);
			$table->boolean('is_explicit')->default(0);
			$table->integer('cover_id')->unsigned()->nullable()->index('tracks_cover_id_foreign');
			$table->boolean('is_downloadable')->default(0);
			$table->float('duration')->unsigned();
			$table->integer('play_count')->unsigned()->default(0);
			$table->integer('view_count')->unsigned()->default(0);
			$table->integer('download_count')->unsigned()->default(0);
			$table->integer('favourite_count')->unsigned()->default(0);
			$table->integer('comment_count')->unsigned()->default(0);
			$table->timestamps();
			$table->softDeletes()->index();
			$table->dateTime('published_at')->nullable()->index();
			$table->dateTime('released_at')->nullable();
			$table->integer('album_id')->unsigned()->nullable()->index('tracks_album_id_foreign');
			$table->integer('track_number')->unsigned()->nullable();
			$table->boolean('is_latest')->default(0);
			$table->string('hash', 32)->nullable();
			$table->boolean('is_listed')->default(1);
			$table->string('source', 40)->default('direct_upload');
			$table->jsonb('metadata')->nullable();
			$table->jsonb('original_tags')->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('tracks');
	}

}
