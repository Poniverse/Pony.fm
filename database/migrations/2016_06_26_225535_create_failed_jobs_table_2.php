<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class CreateFailedJobsTable2
 *
 * This is the PostgreSQL version of CreateFailedJobsTable.
 */
class CreateFailedJobsTable2 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('failed_jobs', function(Blueprint $table)
		{
			$table->increments('id');
			$table->text('connection', 65535);
			$table->text('queue', 65535);
			$table->text('payload');
			$table->dateTime('failed_at')->default('now()');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('failed_jobs');
	}

}
