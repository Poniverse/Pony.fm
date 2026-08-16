<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2026 Feld0.
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

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

/**
 * Refreshes the denormalized track_count of the given playlists from their
 * pivot rows, e.g. after a deleted track is detached from them.
 */
class RecountPlaylistTracks extends Job implements ShouldQueue
{
    use InteractsWithQueue;

    /** Counts must reflect committed rows, never an open transaction's. */
    public $afterCommit = true;

    /** @var int[] */
    protected array $playlistIds;

    public function __construct(array $playlistIds)
    {
        $this->playlistIds = $playlistIds;
    }

    public function handle()
    {
        $this->beforeHandle();

        DB::table('playlists')
            ->whereIn('id', $this->playlistIds)
            ->update([
                'track_count' => DB::raw('(SELECT COUNT(id) FROM playlist_track WHERE playlist_id = playlists.id)'),
            ]);
    }
}
