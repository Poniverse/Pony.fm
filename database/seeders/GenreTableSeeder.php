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

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class GenreTableSeeder extends Seeder
{
    public function run()
    {
        // This table only needs to be filled once.
        if (DB::table('genres')->count() === 0) {
            DB::table('genres')->insert(
                [
                    [
                        'name' => 'Acoustic',
                        'slug' => 'acoustic',
                    ],

                    [
                        'name' => 'Adult Contemporary',
                        'slug' => 'adult-contemporary',
                    ],

                    [
                        'name' => 'Ambient',
                        'slug' => 'ambient',
                    ],

                    [
                        'name' => 'Chiptune',
                        'slug' => 'chiptune',
                    ],

                    [
                        'name' => 'Country',
                        'slug' => 'country',
                    ],

                    [
                        'name' => 'Darkwave',
                        'slug' => 'darkwave',
                    ],

                    [
                        'name' => 'Disco / Funk',
                        'slug' => 'disco-funk',
                    ],

                    [
                        'name' => 'Downtempo',
                        'slug' => 'downtempo',
                    ],

                    [
                        'name' => 'Drum & Bass',
                        'slug' => 'drum-bass',
                    ],

                    [
                        'name' => 'Dubstep',
                        'slug' => 'dubstep',
                    ],

                    [
                        'name' => 'EDM',
                        'slug' => 'edm',
                    ],

                    [
                        'name' => 'Electro',
                        'slug' => 'electro',
                    ],

                    [
                        'name' => 'Eurobeat',
                        'slug' => 'eurobeat',
                    ],

                    [
                        'name' => 'Experimental',
                        'slug' => 'experimental',
                    ],

                    [
                        'name' => 'Hardcore',
                        'slug' => 'hardcore',
                    ],

                    [
                        'name' => 'Hardstyle',
                        'slug' => 'hardstyle',
                    ],

                    [
                        'name' => 'Hip-Hop',
                        'slug' => 'hip-hop',
                    ],

                    [
                        'name' => 'House',
                        'slug' => 'house',
                    ],

                    [
                        'name' => 'IDM',
                        'slug' => 'idm',
                    ],

                    [
                        'name' => 'Jazz',
                        'slug' => 'jazz',
                    ],

                    [
                        'name' => 'Mashup',
                        'slug' => 'mashup',
                    ],

                    [
                        'name' => 'Metal',
                        'slug' => 'metal',
                    ],

                    [
                        'name' => 'Orchestral',
                        'slug' => 'orchestral',
                    ],

                    [
                        'name' => 'Other',
                        'slug' => 'other',
                    ],

                    [
                        'name' => 'Pop',
                        'slug' => 'pop',
                    ],

                    [
                        'name' => 'Progressive',
                        'slug' => 'progressive',
                    ],

                    [
                        'name' => 'Rock',
                        'slug' => 'rock',
                    ],

                    [
                        'name' => 'Ska / Punk',
                        'slug' => 'ska-punk',
                    ],

                    [
                        'name' => 'Synthpop',
                        'slug' => 'synthpop',
                    ],

                    [
                        'name' => 'Trance',
                        'slug' => 'trance',
                    ],
                ]);
        }
    }
}
