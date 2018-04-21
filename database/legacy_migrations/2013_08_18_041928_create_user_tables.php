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

class CreateUserTables extends Migration
{
    public function up()
    {
        Schema::create('resource_users', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->index();

            $table->integer('track_id')->unsigned()->nullable()->index();
            $table->integer('album_id')->unsigned()->nullable()->index();
            $table->integer('playlist_id')->unsigned()->nullable()->index();
            $table->integer('artist_id')->unsigned()->nullable()->index();

            $table->boolean('is_followed');
            $table->boolean('is_favourited');
            $table->boolean('is_pinned');

            $table->integer('view_count');
            $table->integer('play_count');
            $table->integer('download_count');

            $table->foreign('artist_id')->references('id')->on('users')->on_delete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->on_delete('cascade');
            $table->foreign('track_id')->references('id')->on('tracks')->on_delete('cascade');;
            $table->foreign('album_id')->references('id')->on('albums')->on_delete('cascade');;
            $table->foreign('playlist_id')->references('id')->on('playlists')->on_delete('cascade');;

            $table->unique(['user_id', 'track_id', 'album_id', 'playlist_id', 'artist_id'], 'resource_unique');
        });

        Schema::create('resource_log_items', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->nullable()->index();
            $table->integer('log_type')->unsigned();
            $table->string('ip_address', 46)->index();
            $table->integer('track_format_id')->unsigned()->nullable();

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
        Schema::drop('resource_users');
        Schema::drop('resource_log_items');
    }
}
