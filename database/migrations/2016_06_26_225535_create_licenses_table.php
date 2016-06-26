<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateLicensesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('licenses', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('title', 100);
			$table->text('description', 65535);
			$table->boolean('affiliate_distribution');
			$table->boolean('open_distribution');
			$table->boolean('remix');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('licenses');
	}

}
