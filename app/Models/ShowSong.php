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

namespace Poniverse\Ponyfm\Models;

use DB;
use Illuminate\Database\Eloquent\Model;

/**
 * Poniverse\Ponyfm\Models\ShowSong
 *
 * @property integer $id
 * @property string $title
 * @property string $lyrics
 * @property string $slug
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\Poniverse\Ponyfm\Models\Track[] $trackCountRelation
 * @property-read \Illuminate\Database\Eloquent\Collection|\Poniverse\Ponyfm\Models\Track[] $tracks
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\ShowSong whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\ShowSong whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\ShowSong whereLyrics($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\ShowSong whereSlug($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\ShowSong whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\ShowSong whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\ShowSong whereDeletedAt($value)
 * @mixin \Eloquent
 */
class ShowSong extends Model
{
    protected $table = 'show_songs';
    protected $fillable = ['title', 'slug', 'lyrics'];

    public function trackCountRelation()
    {
        return $this->belongsToMany(Track::class)
            ->select(['show_song_id', DB::raw('count(*) as track_count')])
            ->groupBy('show_song_id', 'track_id');
    }

    public function tracks()
    {
        return $this->belongsToMany(Track::class);
    }
}
