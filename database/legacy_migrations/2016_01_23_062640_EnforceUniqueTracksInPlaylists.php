<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Feld0.
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

use App\Models\Playlist;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class EnforceUniqueTracksInPlaylists extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            $playlistIds = DB::table('playlists')->pluck('id');

            foreach ($playlistIds as $playlistId) {
                /** @var Playlist $playlist */

                // Deletes all of a playlist's entries of a
                // duplicate track except for the first one.
                $ids = DB::select(
                    DB::raw(
<<<'EOF'
SELECT id,position FROM playlist_track
WHERE playlist_id = ?
AND track_id IN
(
    SELECT track_id FROM
    (
        SELECT track_id,COUNT(*) as count FROM playlist_track
        WHERE playlist_id = ?
        GROUP BY track_id
    ) as duplicateTracks
    WHERE count > 1
)
ORDER BY position ASC
LIMIT 1,18446744073709551615
EOF
                    ), [$playlistId, $playlistId]);
                $ids = collect($ids)->pluck('id');

                DB::table('playlist_track')
                    ->whereIn('id', $ids)
                    ->delete();

                // Using this instead of $model->fresh(); because that
                // doesn't deal with soft-deleted models.
                $playlist = Playlist::with('tracks')->withTrashed()->find($playlistId);

                $position = 1;
                foreach ($playlist->tracks as $track) {
                    $track->pivot->position = $position;
                    $track->pivot->save();
                    $position++;
                }
            }
        });

        Schema::table('playlist_track', function (Blueprint $table) {
            $table->unique(['playlist_id', 'track_id']);
        });

        Artisan::call('refresh-cache');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('playlist_track', function (Blueprint $table) {
            $table->dropUnique('playlist_track_playlist_id_track_id_unique');
        });
    }
}
