<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Feld0
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

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateActivityDescriptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('activity_types')->where('activity_type', 1)->update(['description' => 'Updates from the Pony.fm team']);
        DB::table('activity_types')->where('activity_type', 2)->update(['description' => 'Someone you follow publishes a track']);
        DB::table('activity_types')->where('activity_type', 3)->update(['description' => 'Someone you follow publishes an album']);
        DB::table('activity_types')->where('activity_type', 4)->update(['description' => 'Someone you follow creates a playlist']);
        DB::table('activity_types')->where('activity_type', 5)->update(['description' => 'You get a new follower']);
        DB::table('activity_types')->where('activity_type', 6)->update(['description' => 'Someone leaves you a comment']);
        DB::table('activity_types')->where('activity_type', 7)->update(['description' => 'Something of yours is favourited']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('activity_types')->where('activity_type', 1)->update(['description' => 'news']);
        DB::table('activity_types')->where('activity_type', 2)->update(['description' => 'followee published a track']);
        DB::table('activity_types')->where('activity_type', 3)->update(['description' => 'followee published an album']);
        DB::table('activity_types')->where('activity_type', 4)->update(['description' => 'followee published a playlist']);
        DB::table('activity_types')->where('activity_type', 5)->update(['description' => 'new follower']);
        DB::table('activity_types')->where('activity_type', 6)->update(['description' => 'new comment']);
        DB::table('activity_types')->where('activity_type', 7)->update(['description' => 'new favourite']);
    }
}
