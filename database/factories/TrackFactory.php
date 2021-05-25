<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015-2017 Feld0.
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

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrackFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\Models\Track::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $user = User::factory()->create();

        return [
            'user_id'           => $user->id,
            'hash'              => $this->faker->md5,
            'title'             => $this->faker->sentence(5),
            'slug'              => $this->faker->slug,
            'track_type_id'     => \App\Models\TrackType::UNCLASSIFIED_TRACK,
            'track_number'      => null,
            'description'       => $this->faker->paragraph(5),
            'lyrics'            => $this->faker->paragraph(5),
            'is_vocal'          => true,
            'is_explicit'       => false,
            'is_downloadable'   => true,
            'is_listed'         => true,
            'metadata'          => '{"this":{"is":["very","random","metadata"]}}',
            'duration'          => $this->faker->randomFloat(null, 30, 600),
        ];
    }
}