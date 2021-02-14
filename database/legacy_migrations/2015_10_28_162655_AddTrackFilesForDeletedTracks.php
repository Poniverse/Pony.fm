<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Feld0.
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
use Poniverse\Ponyfm\Models\Track;

class AddTrackFilesForDeletedTracks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 2015_05_25_011121_create_track_files_table.php only created
        // track_files records for non-deleted tracks. This migration
        // adds them for deleted tracks, too.

        $tracks = Track::with('trackFiles')
            ->onlyTrashed()
            ->get();

        foreach ($tracks as $track) {
            if ($track->trackFiles->count() === 0 && $track->source !== 'mlpma') {
                foreach (Track::$Formats as $name => $item) {
                    DB::table('track_files')->insert(
                        [
                            'track_id' => $track->id,
                            'is_master' => $name === 'FLAC' ? true : false,
                            'format' => $name,
                            'created_at' => $track->created_at,
                            'updated_at' => Carbon\Carbon::now(),
                        ]
                    );
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // There's no need to undo this one!
    }
}
