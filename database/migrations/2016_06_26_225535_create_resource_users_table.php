<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateResourceUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('resource_users', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->index();
            $table->integer('track_id')->unsigned()->nullable()->index();
            $table->integer('album_id')->unsigned()->nullable()->index();
            $table->integer('playlist_id')->unsigned()->nullable()->index();
            $table->integer('artist_id')->unsigned()->nullable()->index();
            $table->boolean('is_followed')->default(false);
            $table->boolean('is_favourited')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('play_count')->default(0);
            $table->unsignedInteger('download_count')->default(0);
            $table->unique(['user_id', 'track_id', 'album_id', 'playlist_id', 'artist_id'], 'resource_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('resource_users');
    }
}
