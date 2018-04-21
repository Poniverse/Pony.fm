<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Feld0
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

class AddSensibleDefaults extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tracks', function(Blueprint $table){
            $table->boolean('is_listed')->default(true)->change();
            $table->boolean('is_explicit')->default(false)->change();
            $table->boolean('is_vocal')->default(false)->change();
            $table->boolean('is_downloadable')->default(false)->change();

            $table->unsignedInteger('play_count')->default(0)->change();
            $table->unsignedInteger('view_count')->default(0)->change();
            $table->unsignedInteger('download_count')->default(0)->change();
            $table->unsignedInteger('favourite_count')->default(0)->change();
            $table->unsignedInteger('comment_count')->default(0)->change();
        });

        Schema::table('users', function(Blueprint $table){
            $table->boolean('can_see_explicit_content')->default(false)->change();
            $table->text('bio')->default('')->change();
            $table->unsignedInteger('track_count')->default(0)->change();
            $table->unsignedInteger('comment_count')->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // This migration is not reversible.
    }
}
