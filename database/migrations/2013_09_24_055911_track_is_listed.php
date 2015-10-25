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

class TrackIsListed extends Migration
{
    public function up()
    {
        Schema::table('tracks', function ($table) {
            $table->boolean('is_listed')->notNullable()->indexed();
        });

        DB::update('
            UPDATE
                tracks
            SET
                is_listed = true');
    }

    public function down()
    {
        Schema::table('tracks', function ($table) {
            $table->dropColumn('is_listed');
        });
    }
}
