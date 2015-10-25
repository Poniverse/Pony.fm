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

class CreateAlbums extends Migration
{
    public function up()
    {
        Schema::create('albums', function ($table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('title')->index();
            $table->string('slug')->index();
            $table->text('description');
            $table->integer('cover_id')->unsigned()->nullable();

            $table->integer('track_count')->unsigned();
            $table->integer('view_count')->unsigned();
            $table->integer('download_count')->unsigned();
            $table->integer('favourite_count')->unsigned();
            $table->integer('comment_count')->unsigned();

            $table->timestamps();
            $table->timestamp('deleted_at')->nullable()->index();

            $table->foreign('cover_id')->references('id')->on('images');
            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::table('tracks', function ($table) {
            $table->integer('album_id')->unsigned()->nullable();
            $table->integer('track_number')->unsigned()->nullable();

            $table->foreign('album_id')->references('id')->on('albums');
        });
    }

    public function down()
    {
        Schema::table('tracks', function ($table) {
            $table->dropForeign('tracks_album_id_foreign');
            $table->dropColumn('album_id');
            $table->dropColumn('track_number');
        });

        Schema::drop('albums');
    }
}
