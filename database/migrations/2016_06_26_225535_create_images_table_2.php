<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class CreateImagesTable2
 *
 * This is the PostgreSQL version of CreateImagesTable.
 */
class CreateImagesTable2 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('images', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('filename', 256);
			$table->string('mime', 100);
			$table->string('extension', 32);
			$table->unsignedInteger('size');
			$table->string('hash', 32)->index();
			$table->unsignedInteger('uploaded_by')->index();
			$table->timestamps();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('images');
	}

}
