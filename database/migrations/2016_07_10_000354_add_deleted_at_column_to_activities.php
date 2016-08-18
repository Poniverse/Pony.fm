<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Peter Deltchev
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

class AddDeletedAtColumnToActivities extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('activities', function(Blueprint $table) {
            $table->softDeletes()->index();
        });

        // Retroactively fix activities that should be marked as deleted.
        // Tracks
        DB::table('activities')
            ->where('resource_type', 2)
            ->join('tracks', 'activities.resource_id', '=', 'tracks.id')
            ->whereNotNull('tracks.deleted_at')
            ->update(['deleted_at' => DB::raw('tracks.deleted_at')]);

        // Albums
        DB::table('activities')
            ->where('resource_type', 3)
            ->join('albums', 'activities.resource_id', '=', 'albums.id')
            ->whereNotNull('albums.deleted_at')
            ->update(['deleted_at' => DB::raw('albums.deleted_at')]);

        // Playlists
        DB::table('activities')
            ->where('resource_type', 4)
            ->join('playlists', 'activities.resource_id', '=', 'playlists.id')
            ->whereNotNull('playlists.deleted_at')
            ->update(['deleted_at' => DB::raw('playlists.deleted_at')]);

        // Comments
        DB::table('activities')
            ->where('resource_type', 5)
            ->join('comments', 'activities.resource_id', '=', 'comments.id')
            ->whereNotNull('comments.deleted_at')
            ->update(['deleted_at' => DB::raw('comments.deleted_at')]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}
