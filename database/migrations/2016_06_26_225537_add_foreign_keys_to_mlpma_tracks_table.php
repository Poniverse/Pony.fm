<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToMlpmaTracksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mlpma_tracks', function (Blueprint $table) {
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
        Schema::table('mlpma_tracks', function (Blueprint $table) {
            $table->dropForeign('mlpma_tracks_track_id_foreign');
        });
    }
}
