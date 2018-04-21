<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Feld0
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

class CreateComments extends Migration
{
    public function up()
    {
        Schema::create('comments', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('ip_address', 46);
            $table->text('content');

            $table->timestamps();
            $table->timestamp('deleted_at')->nullable()->index();

            $table->integer('profile_id')->unsigned()->nullable()->index();
            $table->integer('track_id')->unsigned()->nullable()->index();
            $table->integer('album_id')->unsigned()->nullable()->index();
            $table->integer('playlist_id')->unsigned()->nullable()->index();

            $table->foreign('profile_id')->references('id')->on('users');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('track_id')->references('id')->on('tracks');
            $table->foreign('album_id')->references('id')->on('albums');
            $table->foreign('playlist_id')->references('id')->on('playlists');
        });
    }

    public function down()
    {
        Schema::table('comments', function ($table) {
            $table->dropForeign('comments_user_id_foreign');
            $table->dropForeign('comments_track_id_foreign');
            $table->dropForeign('comments_album_id_foreign');
            $table->dropForeign('comments_playlist_id_foreign');
        });
        Schema::drop('comments');
    }
}
