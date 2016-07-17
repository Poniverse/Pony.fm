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

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewIndices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ('sqlite' !== DB::getDriverName()) {
            DB::statement('ALTER TABLE `show_songs` ADD FULLTEXT show_songs_title_fulltext (title)');
        }

        Schema::table('images', function ($table) {
            $table->index('hash');
        });

        Schema::table('track_files', function ($table) {
            $table->index('is_master');
            $table->index('format');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if ('sqlite' !== DB::getDriverName()) {
            DB::statement('ALTER TABLE `show_songs` DROP INDEX show_songs_title_fulltext');
        }

        Schema::table('images', function ($table) {
            $table->dropIndex('images_hash_index');
        });

        Schema::table('track_files', function ($table) {
            $table->dropIndex('track_files_is_master_index');
            $table->dropIndex('track_files_format_index');
        });
    }
}
