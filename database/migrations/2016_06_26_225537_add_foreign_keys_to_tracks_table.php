<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToTracksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tracks', function (Blueprint $table) {
            $table->foreign('album_id')->references('id')->on('albums')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('cover_id')->references('id')->on('images')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('genre_id')->references('id')->on('genres')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('license_id')->references('id')->on('licenses')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('track_type_id')->references('id')->on('track_types')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tracks', function (Blueprint $table) {
            $table->dropForeign('tracks_album_id_foreign');
            $table->dropForeign('tracks_cover_id_foreign');
            $table->dropForeign('tracks_genre_id_foreign');
            $table->dropForeign('tracks_license_id_foreign');
            $table->dropForeign('tracks_track_type_id_foreign');
            $table->dropForeign('tracks_user_id_foreign');
        });
    }
}
