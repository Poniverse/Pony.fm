<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->foreign('album_id')->references('id')->on('albums')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('playlist_id')->references('id')->on('playlists')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('profile_id')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('track_id')->references('id')->on('tracks')->onUpdate('RESTRICT')->onDelete('RESTRICT');
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
        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign('comments_album_id_foreign');
            $table->dropForeign('comments_playlist_id_foreign');
            $table->dropForeign('comments_profile_id_foreign');
            $table->dropForeign('comments_track_id_foreign');
            $table->dropForeign('comments_user_id_foreign');
        });
    }
}
