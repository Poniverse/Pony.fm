<?php

use Illuminate\Database\Migrations\Migration;

class CreateMlpmaTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('mlpma_tracks', function(\Illuminate\Database\Schema\Blueprint $table) {
			$table->increments('id');
			$table->integer('track_id')->unsigned()->index();
			$table->string('path')->index();
			$table->string('filename')->index();
			$table->string('extension')->index();
			$table->dateTime('imported_at');
			$table->longText('parsed_tags');
			$table->longText('raw_tags');

			$table->foreign('track_id')->references('id')->on('tracks');
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
