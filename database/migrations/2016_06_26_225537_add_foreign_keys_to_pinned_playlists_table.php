<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToPinnedPlaylistsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pinned_playlists', function (Blueprint $table) {
            $table->foreign('playlist_id')->references('id')->on('playlists')->onUpdate('RESTRICT')->onDelete('RESTRICT');
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
        Schema::table('pinned_playlists', function (Blueprint $table) {
            $table->dropForeign('pinned_playlists_playlist_id_foreign');
            $table->dropForeign('pinned_playlists_user_id_foreign');
        });
    }
}
