<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOauth2TokensTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('oauth2_tokens', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('user_id');
			$table->integer('external_user_id');
			$table->text('access_token', 65535);
			$table->dateTime('expires')->default('now()');
			$table->text('refresh_token', 65535);
			$table->string('type');
			$table->string('service');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('oauth2_tokens');
	}

}
