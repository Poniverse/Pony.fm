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

class CreateTracksTable extends Migration
{
    public function up()
    {
        Schema::create('licenses', function ($table) {
            $table->increments('id');
            $table->string('title', 100);
            $table->text('description');
            $table->boolean('affiliate_distribution');
            $table->boolean('open_distribution');
            $table->boolean('remix');
        });

        Schema::create('genres', function ($table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->string('slug', 200)->index();
        });

        Schema::create('track_types', function ($table) {
            $table->increments('id');
            $table->string('title');
            $table->string('editor_title');
        });

        Schema::create('tracks', function ($table) {
            $table->increments('id');

            $table->integer('user_id')->unsigned();
            $table->integer('license_id')->unsigned()->nullable()->default(null);
            $table->integer('genre_id')->unsigned()->nullable()->index()->default(null);
            $table->integer('track_type_id')->unsigned()->nullable()->default(null);

            $table->string('title', 100)->index();
            $table->string('slug', 200)->index();
            $table->text('description')->nullable();
            $table->text('lyrics')->nullable();
            $table->boolean('is_vocal');
            $table->boolean('is_explicit');
            $table->integer('cover_id')->unsigned()->nullable();
            $table->boolean('is_downloadable');
            $table->float('duration')->unsigned();

            $table->integer('play_count')->unsigned();
            $table->integer('view_count')->unsigned();
            $table->integer('download_count')->unsigned();
            $table->integer('favourite_count')->unsigned();
            $table->integer('comment_count')->unsigned();

            $table->timestamps();
            $table->timestamp('deleted_at')->nullable()->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('released_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('license_id')->references('id')->on('licenses');
            $table->foreign('genre_id')->references('id')->on('genres')->on_update('cascade');
            $table->foreign('track_type_id')->references('id')->on('track_types')->on_update('cascade');
        });

        DB::table('licenses')->insert([
            'title' => 'Personal',
            'description' => 'Only you and Pony.fm are allowed to distribute and broadcast the track.',
            'affiliate_distribution' => 0,
            'open_distribution' => 0,
            'remix' => 0,
        ]);

        DB::table('licenses')->insert([
            'title' => 'Broadcast',
            'description' => 'You, Pony.fm, and its affiliates may distribute and broadcast the track.',
            'affiliate_distribution' => 1,
            'open_distribution' => 0,
            'remix' => 0,
        ]);

        DB::table('licenses')->insert([
            'title' => 'Open',
            'description' => 'Anyone is permitted to broadcast and distribute the song in its original form, with attribution to you.',
            'affiliate_distribution' => 1,
            'open_distribution' => 1,
            'remix' => 0,
        ]);

        DB::table('licenses')->insert([
            'title' => 'Remix',
            'description' => 'Anyone is permitted to broadcast and distribute the song in any form, or create derivative works based on it for any purpose, with attribution to you.',
            'affiliate_distribution' => 1,
            'open_distribution' => 1,
            'remix' => 1,
        ]);

        DB::table('track_types')->insert([
            'title' => 'Original Song',
            'editor_title' => 'an original song',
        ]);

        DB::table('track_types')->insert([
            'title' => 'Official Song Remix',
            'editor_title' => 'a remix of an official song',
        ]);

        DB::table('track_types')->insert([
            'title' => 'Fan Song Remix',
            'editor_title' => 'a remix of a fan song',
        ]);

        DB::table('track_types')->insert([
            'title' => 'Ponified Song',
            'editor_title' => 'a non-pony song, turned pony',
        ]);

        DB::table('track_types')->insert([
            'title' => 'Official Show Audio Remix',
            'editor_title' => 'a remix of official show audio',
        ]);
    }

    public function down()
    {
        Schema::drop('tracks');
        Schema::drop('licenses');
        Schema::drop('track_types');
        Schema::drop('genres');
    }
}
