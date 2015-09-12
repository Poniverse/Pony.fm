<?php

use Illuminate\Database\Migrations\Migration;

class CreateFollowers extends Migration
{
    public function up()
    {
        Schema::create('followers', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->index();

            $table->integer('artist_id')->unsigned()->nullable()->index();
            $table->integer('playlist_id')->unsigned()->nullable()->index();

            $table->timestamp('created_at');

            $table->foreign('user_id')->references('id')->on('users')->on_delete('cascade');
            $table->foreign('artist_id')->references('id')->on('users')->on_delete('cascade');
            $table->foreign('playlist_id')->references('id')->on('playlists');
        });
    }

    public function down()
    {
        Schema::drop('followers');
    }
}