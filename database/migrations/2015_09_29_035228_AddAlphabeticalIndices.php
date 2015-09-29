<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAlphabeticalIndices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('display_name');
            $table->index('track_count');
        });

        Schema::table('playlists', function (Blueprint $table) {
            $table->index('title');
            $table->index('track_count');
            $table->index('is_public');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_display_name_index');
            $table->dropIndex('users_track_count_index');
        });

        Schema::table('playlists', function (Blueprint $table) {
            $table->dropIndex('playlists_title_index');
            $table->dropIndex('playlists_track_count_index');
            $table->dropIndex('playlists_is_public_index');
        });
    }
}
