<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMlpmaTracksTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('mlpma_tracks', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('track_id')->unsigned()->index();
			$table->string('path')->index();
			$table->string('filename')->index();
			$table->string('extension')->index();
			$table->dateTime('imported_at');
			$table->text('parsed_tags');
			$table->text('raw_tags');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('mlpma_tracks');
	}

}
