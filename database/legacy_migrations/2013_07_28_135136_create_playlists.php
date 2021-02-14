<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Feld0.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

use Illuminate\Database\Migrations\Migration;

class CreatePlaylists extends Migration
{
    public function up()
    {
        Schema::create('playlists', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->index();
            $table->string('title');
            $table->string('slug');
            $table->text('description');
            $table->boolean('is_public');

            $table->integer('track_count')->unsigned();
            $table->integer('view_count')->unsigned();
            $table->integer('download_count')->unsigned();
            $table->integer('favourite_count')->unsigned();
            $table->integer('follow_count')->unsigned();
            $table->integer('comment_count')->unsigned();

            $table->timestamps();
            $table->date('deleted_at')->nullable()->index();

            $table->foreign('user_id')->references('id')->on('users')->on_update('cascade');
        });

        Schema::create('playlist_track', function ($table) {
            $table->increments('id');
            $table->timestamps();
            $table->integer('playlist_id')->unsigned()->index();
            $table->integer('track_id')->unsigned()->index();
            $table->integer('position')->unsigned();

            $table->foreign('playlist_id')->references('id')->on('playlists')->on_update('cascade')->on_delete('cascade');
            $table->foreign('track_id')->references('id')->on('tracks')->on_update('cascade');
        });

        Schema::create('pinned_playlists', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->index();
            $table->integer('playlist_id')->unsigned()->index();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->on_update('cascade');
            $table->foreign('playlist_id')->references('id')->on('playlists')->on_update('cascade');
        });
    }

    public function down()
    {
        Schema::table('playlist_track', function ($table) {
            $table->dropForeign('playlist_track_playlist_id_foreign');
            $table->dropForeign('playlist_track_track_id_foreign');
        });

        Schema::drop('playlist_track');

        Schema::drop('pinned_playlists');

        Schema::table('playlists', function ($table) {
            $table->dropForeign('playlists_user_id_foreign');
        });

        Schema::drop('playlists');
    }
}
