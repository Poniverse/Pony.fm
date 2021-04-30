<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePonifyTracks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ponify_tracks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('track_id')->unsigned()->index();
            $table->string('path')->index();
            $table->string('filename')->index();
            $table->string('extension')->index();
            $table->dateTime('imported_at');
            $table->text('parsed_tags');
            $table->text('raw_tags');
        });

        Schema::table('ponify_tracks', function (Blueprint $table) {
            $table->foreign('track_id')->references('id')->on('tracks')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ponify_tracks', function (Blueprint $table) {
            $table->dropForeign('ponify_tracks_track_id_foreign');
        });

        Schema::drop('ponify_tracks');
    }
}
