<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Peter Deltchev
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

class CreateFavourites extends Migration
{
    public function up()
    {
        Schema::create('favourites', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->index();

            $table->integer('track_id')->unsigned()->nullable()->index();
            $table->integer('album_id')->unsigned()->nullable()->index();
            $table->integer('playlist_id')->unsigned()->nullable()->index();

            $table->timestamp('created_at');

            $table->foreign('user_id')->references('id')->on('users')->on_delete('cascade');
            $table->foreign('track_id')->references('id')->on('tracks');
            $table->foreign('album_id')->references('id')->on('albums');
            $table->foreign('playlist_id')->references('id')->on('playlists');
        });
    }

    public function down()
    {
        Schema::table('favourites', function ($table) {
            $table->dropForeign('favourites_user_id_foreign');
            $table->dropForeign('favourites_track_id_foreign');
            $table->dropForeign('favourites_album_id_foreign');
            $table->dropForeign('favourites_playlist_id_foreign');
        });

        Schema::drop('favourites');
    }
}
