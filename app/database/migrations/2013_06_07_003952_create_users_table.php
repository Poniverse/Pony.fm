<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration {
	public function up() {
		Schema::create('users', function(Blueprint $table) {
			$table->increments('id');
			$table->string('display_name', 255);
			$table->string('mlpforums_name')->nullable();
			$table->boolean('sync_names')->default(true);
			$table->string('password_hash', 32);
			$table->string('password_salt', 5);
			$table->string('email', 150)->indexed();
			$table->string('gravatar')->nullable();
			$table->string('slug');
			$table->boolean('uses_gravatar')->default(true);
			$table->boolean('can_see_explicit_content');
			$table->text('bio');
			$table->integer('track_count')->unsigned();
			$table->integer('comment_count')->unsigned();
			$table->timestamps();
		});

		Schema::create('roles', function($table){
			$table->increments('id');
			$table->string('name');
		});

		Schema::create('role_user', function($table){
			$table->increments('id');
			$table->integer('user_id')->unsigned()->index();
			$table->integer('role_id')->unsigned()->index();
			$table->timestamps();

			$table->foreign('user_id')->references('id')->on('users');
			$table->foreign('role_id')->references('id')->on('roles');
		});

		Schema::create('cache', function($table){
			$table->string('key')->index();
			$table->text('value');
			$table->integer('expiration')->unsigned()->index();
		});

		DB::table('roles')->insert(['name' => 'super_admin']);
		DB::table('roles')->insert(['name' => 'admin']);
		DB::table('roles')->insert(['name' => 'moderator']);
		DB::table('roles')->insert(['name' => 'user']);
	}

	public function down() {
		Schema::drop('cache');
		Schema::drop('role_user');
		Schema::drop('roles');
		Schema::drop('users');
	}
}
