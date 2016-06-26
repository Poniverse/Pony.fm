<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTrackFilesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('track_files', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('track_id')->unsigned()->index('track_files_track_id_foreign');
			$table->boolean('is_master')->default(0)->index();
			$table->string('format')->index();
			$table->timestamps();
			$table->boolean('is_cacheable')->default(0)->index();
			$table->boolean('status')->default(0);
			$table->dateTime('expires_at')->nullable()->index();
			$table->integer('filesize')->unsigned()->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('track_files');
	}

}
