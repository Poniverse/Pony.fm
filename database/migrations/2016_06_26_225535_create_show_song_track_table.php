<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateShowSongTrackTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('show_song_track', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('track_id')->unsigned()->index('show_song_track_track_id_foreign');
            $table->integer('show_song_id')->unsigned()->index('show_song_track_show_song_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('show_song_track');
    }
}
