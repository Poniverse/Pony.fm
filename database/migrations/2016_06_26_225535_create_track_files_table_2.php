<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class CreateTrackFilesTable2
 *
 * This is the PostgreSQL version of CreateTrackFilesTable.
 */
class CreateTrackFilesTable2 extends Migration {

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
			$table->boolean('is_master')->default(false)->index();
			$table->string('format')->index();
			$table->timestamps();
			$table->boolean('is_cacheable')->default(false)->index();
			$table->unsignedTinyInteger('status')->default(false);
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
