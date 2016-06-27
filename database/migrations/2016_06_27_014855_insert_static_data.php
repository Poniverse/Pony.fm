<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Peter Deltchev
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

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InsertStaticData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('licenses')->insert([
            'id' => 1,
            'title' => 'Personal',
            'description' => 'Only you and Pony.fm are allowed to distribute and broadcast the track.',
            'affiliate_distribution' => 0,
            'open_distribution' => 0,
            'remix' => 0
        ]);

        DB::table('licenses')->insert([
            'id' => 2,
            'title' => 'Broadcast',
            'description' => 'You, Pony.fm, and its affiliates may distribute and broadcast the track.',
            'affiliate_distribution' => 1,
            'open_distribution' => 0,
            'remix' => 0
        ]);

        DB::table('licenses')->insert([
            'id' => 3,
            'title' => 'Open',
            'description' => 'Anyone is permitted to broadcast and distribute the song in its original form, with attribution to you.',
            'affiliate_distribution' => 1,
            'open_distribution' => 1,
            'remix' => 0
        ]);

        DB::table('licenses')->insert([
            'id' => 4,
            'title' => 'Remix',
            'description' => 'Anyone is permitted to broadcast and distribute the song in any form, or create derivative works based on it for any purpose, with attribution to you.',
            'affiliate_distribution' => 1,
            'open_distribution' => 1,
            'remix' => 1
        ]);

        DB::table('track_types')->insert([
            'id' => 1,
            'title' => 'Original Song',
            'editor_title' => 'an original song'
        ]);

        DB::table('track_types')->insert([
            'id' => 2,
            'title' => 'Official Song Remix',
            'editor_title' => 'a remix of an official song'
        ]);

        DB::table('track_types')->insert([
            'id' => 3,
            'title' => 'Fan Song Remix',
            'editor_title' => 'a remix of a fan song'
        ]);

        DB::table('track_types')->insert([
            'id' => 4,
            'title' => 'Ponified Song',
            'editor_title' => 'a non-pony song, turned pony'
        ]);

        DB::table('track_types')->insert([
            'id' => 5,
            'title' => 'Official Show Audio Remix',
            'editor_title' => 'a remix of official show audio'
        ]);

        DB::table('track_types')->insert([
            'id' => 6,
            'title' => 'Unclassified',
            'editor_title' => 'an unclassified track'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('licenses')->whereIn('id', [1, 2, 3, 4])->delete();
        DB::table('track_types')->whereIn('id', [1, 2, 3, 4, 5, 6])->delete();
    }
}
