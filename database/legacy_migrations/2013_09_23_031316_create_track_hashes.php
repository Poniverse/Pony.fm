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

use Poniverse\Ponyfm\Models\Track;
use Illuminate\Database\Migrations\Migration;

class CreateTrackHashes extends Migration
{
    public function up()
    {
        Schema::table('tracks', function ($table) {
            $table->string('hash', 32)->nullable()->indexed();
        });

        foreach (Track::with('user')->get() as $track) {
            $track->updateHash();
            $track->save();
        }

        Schema::table('tracks', function ($table) {
            $table->string('hash', 32)->notNullable()->change();
        });

    }

    public function down()
    {
        Schema::table('tracks', function ($table) {
            $table->dropColumn('hash');
        });
    }
}
