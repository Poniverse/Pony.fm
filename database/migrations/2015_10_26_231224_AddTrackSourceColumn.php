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

class AddTrackSourceColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tracks', function(Blueprint $table){
            $table->string('source', 40)->default('direct_upload');
        });

        // Mark MLPMA tracks retroactively
        // --> The default value in the database, set above, will
        //     be used automatically for all non-MLPMA tracks.
        $tracks = DB::table('tracks')
            ->join('mlpma_tracks', 'mlpma_tracks.track_id', '=', 'tracks.id');

        $tracks->whereNotNull('mlpma_tracks.id')->update(['source' => 'mlpma']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tracks', function(Blueprint $table){
            $table->dropColumn('source');
        });
    }
}
