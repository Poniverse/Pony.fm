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

namespace Poniverse\Ponyfm\Http\Controllers\Api\Web;

use Poniverse\Ponyfm\Models\Genre;
use Poniverse\Ponyfm\Http\Controllers\ApiControllerBase;
use Poniverse\Ponyfm\Models\License;
use Poniverse\Ponyfm\Models\ShowSong;
use Poniverse\Ponyfm\Models\TrackType;
use DB;

class TaxonomiesController extends ApiControllerBase
{
    public function getAll()
    {
        return \Response::json([
            'licenses' => License::all()->toArray(),
            'genres' => Genre::with('trackCountRelation')
                ->orderBy('name')
                ->get()
                ->toArray(),
            'track_types' => TrackType::select(
                'track_types.*',
                DB::raw('(SELECT COUNT(id) FROM tracks WHERE tracks.track_type_id = track_types.id AND tracks.published_at IS NOT NULL) AS track_count')
            )
                ->where('id', '!=', TrackType::UNCLASSIFIED_TRACK)
                ->get()->toArray(),
            'show_songs' => ShowSong::select(
                'title',
                'id',
                'slug',
                DB::raw('(SELECT COUNT(tracks.id) FROM show_song_track INNER JOIN tracks ON tracks.id = show_song_track.track_id WHERE show_song_track.show_song_id = show_songs.id AND tracks.published_at IS NOT NULL) AS track_count')
            )->orderBy('title')->get()->toArray()
        ], 200);
    }
}
