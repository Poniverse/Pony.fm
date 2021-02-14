<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class CreateUsersTable2.
 *
 * This is the PostgreSQL version of CreateUsersTable.
 */
class CreateUsersTable2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('display_name')->index();
            $table->string('username')->nullable();
            $table->boolean('sync_names')->default(true);
            $table->string('email', 150)->nullable();
            $table->string('gravatar')->nullable();
            $table->string('slug')->unique();
            $table->boolean('uses_gravatar')->default(true);
            $table->boolean('can_see_explicit_content')->default(false);
            $table->text('bio', 65535)->default('');
            $table->integer('track_count')->unsigned()->default(0)->index();
            $table->integer('comment_count')->unsigned()->default(0);
            $table->timestamps();
            $table->integer('avatar_id')->unsigned()->nullable()->index('users_avatar_id_foreign');
            $table->string('remember_token', 100)->nullable();
            $table->boolean('is_archived')->default(false)->index();
            $table->dateTime('disabled_at')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('users');
    }
}
