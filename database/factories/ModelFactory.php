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

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

use Poniverse\Ponyfm\Models\User;

$factory->define(Poniverse\Ponyfm\Models\User::class, function (\Faker\Generator $faker) {
    return [
        'username'      => $faker->userName,
        'display_name'  => $faker->userName,
        'slug'          => $faker->slug,
        'email'         => $faker->email,
        'can_see_explicit_content' => true,
        'uses_gravatar' => true,
        'bio'           => $faker->paragraph,
        'track_count'   => 0,
        'comment_count' => 0,
    ];
});

$factory->define(\Poniverse\Ponyfm\Models\Track::class, function(\Faker\Generator $faker) {
    $user = factory(User::class)->create();

    return [
        'user_id'           => $user->id,
        'hash'              => $faker->md5,
        'title'             => $faker->sentence(5),
        'track_type_id'     => \Poniverse\Ponyfm\Models\TrackType::UNCLASSIFIED_TRACK,
        'genre'             => $faker->word,
        'album'             => $faker->sentence(5),
        'track_number'      => null,
        'description'       => $faker->paragraph(5),
        'lyrics'            => $faker->paragraph(5),
        'is_vocal'          => true,
        'is_explicit'       => false,
        'is_downloadable'   => true,
        'is_listed'         => true,
        'metadata'          => '{"this":{"is":["very","random","metadata"]}}'
    ];
});
