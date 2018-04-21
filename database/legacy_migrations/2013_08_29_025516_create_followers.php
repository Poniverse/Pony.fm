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

class CreateFollowers extends Migration
{
    public function up()
    {
        Schema::create('followers', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->index();

            $table->integer('artist_id')->unsigned()->nullable()->index();
            $table->integer('playlist_id')->unsigned()->nullable()->index();

            $table->timestamp('created_at');

            $table->foreign('user_id')->references('id')->on('users')->on_delete('cascade');
            $table->foreign('artist_id')->references('id')->on('users')->on_delete('cascade');
            $table->foreign('playlist_id')->references('id')->on('playlists');
        });
    }

    public function down()
    {
        Schema::drop('followers');
    }
}
