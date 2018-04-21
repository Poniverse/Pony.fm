<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015-2017 Feld0
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
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
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
        'slug'              => $faker->slug,
        'track_type_id'     => \Poniverse\Ponyfm\Models\TrackType::UNCLASSIFIED_TRACK,
        'track_number'      => null,
        'description'       => $faker->paragraph(5),
        'lyrics'            => $faker->paragraph(5),
        'is_vocal'          => true,
        'is_explicit'       => false,
        'is_downloadable'   => true,
        'is_listed'         => true,
        'metadata'          => '{"this":{"is":["very","random","metadata"]}}',
        'duration'          => $faker->randomFloat(null, 30, 600)
    ];
});

$factory->define(\Poniverse\Ponyfm\Models\Genre::class, function(\Faker\Generator $faker) {
    return [
        'name' => $faker->word,
        'slug' => $faker->slug,
    ];
});

/**
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $title
 * @property string $slug
 * @property string $description
 * @property integer $cover_id
 * @property integer $track_count
 * @property integer $view_count
 * @property integer $download_count
 * @property integer $favourite_count
 * @property integer $comment_count
 * @property \Carbon\Carbon $created_at
 * @property string $updated_at
 * @property \Carbon\Carbon $deleted_at
 */
$factory->define(\Poniverse\Ponyfm\Models\Album::class, function(\Faker\Generator $faker) {
    return [
        'title'         => $faker->sentence(5),
        'slug'          => $faker->slug,
        'description'   => $faker->paragraph(5),
    ];
});
