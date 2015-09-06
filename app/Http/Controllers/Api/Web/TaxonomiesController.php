<?php

namespace App\Http\Controllers\Api\Web;

use App\Genre;
use App\Http\Controllers\ApiControllerBase;
use App\License;
use App\ShowSong;
use App\TrackType;
use Illuminate\Support\Facades\DB;

class TaxonomiesController extends ApiControllerBase
{
    public function getAll()
    {
        return \Response::json([
            'licenses' => License::all()->toArray(),
            'genres' => Genre::select('genres.*',
                DB::raw('(SELECT COUNT(id) FROM tracks WHERE tracks.genre_id = genres.id AND tracks.published_at IS NOT NULL) AS track_count'))->orderBy('name')->get()->toArray(),
            'track_types' => TrackType::select('track_types.*',
                DB::raw('(SELECT COUNT(id) FROM tracks WHERE tracks.track_type_id = track_types.id AND tracks.published_at IS NOT NULL) AS track_count'))->get()->toArray(),
            'show_songs' => ShowSong::select('title', 'id', 'slug',
                DB::raw('(SELECT COUNT(tracks.id) FROM show_song_track INNER JOIN tracks ON tracks.id = show_song_track.track_id WHERE show_song_track.show_song_id = show_songs.id AND tracks.published_at IS NOT NULL) AS track_count'))->get()->toArray()
        ], 200);
    }
}