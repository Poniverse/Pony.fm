<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Feld0
 * Copyright (C) 2015 Kelvin Zhang.
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
use Illuminate\Database\Schema\Blueprint;

class UpdateTrackFilesWithCache extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('track_files', function (Blueprint $table) {
            $table->boolean('is_cacheable')->default(false)->index();
            $table->tinyInteger('is_in_progress')->default(false);
            $table->dateTime('expires_at')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // These are separated to avoid a weird "no such index" error with SQLite.
        Schema::table('track_files', function (Blueprint $table) {
            $table->dropColumn('is_cacheable');
        });

        Schema::table('track_files', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });

        Schema::table('track_files', function (Blueprint $table) {
            $table->dropColumn('is_in_progress');
        });
    }
}
