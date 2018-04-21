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

class CreateImagesTable extends Migration
{
    public function up()
    {
        Schema::create('images', function ($table) {
            $table->increments('id');
            $table->string('filename', 256);
            $table->string('mime', 100);
            $table->string('extension', 32);
            $table->integer('size');
            $table->string('hash', 32);
            $table->integer('uploaded_by')->unsigned();
            $table->timestamps();

            $table->foreign('uploaded_by')->references('id')->on('users');
        });

        Schema::table('users', function ($table) {
            $table->integer('avatar_id')->unsigned()->nullable();
            $table->foreign('avatar_id')->references('id')->on('images');
        });

        DB::table('tracks')->update(['cover_id' => null]);

        Schema::table('tracks', function ($table) {
            $table->foreign('cover_id')->references('id')->on('images');
        });
    }

    public function down()
    {
        Schema::table('tracks', function ($table) {
            $table->dropForeign('tracks_cover_id_foreign');
        });

        Schema::table('users', function ($table) {
            $table->dropForeign('users_avatar_id_foreign');
            $table->dropColumn('avatar_id');
        });

        Schema::drop('images');
    }
}
