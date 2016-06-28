<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCommentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('comments', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('user_id')->unsigned()->index('comments_user_id_foreign');
			$table->string('ip_address', 46)->nullable();
			$table->text('content', 65535);
			$table->timestamps();
			$table->softDeletes()->index();
			$table->integer('profile_id')->unsigned()->nullable()->index();
			$table->integer('track_id')->unsigned()->nullable()->index();
			$table->integer('album_id')->unsigned()->nullable()->index();
			$table->integer('playlist_id')->unsigned()->nullable()->index();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('comments');
	}

}
