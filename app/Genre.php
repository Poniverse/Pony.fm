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

namespace Poniverse\Ponyfm;

use DB;
use Illuminate\Database\Eloquent\Relations\Relation;
use Poniverse\Ponyfm\Traits\SlugTrait;
use Illuminate\Database\Eloquent\Model;
use URL;
use Venturecraft\Revisionable\RevisionableTrait;

class Genre extends Model
{
    protected $table = 'genres';
    protected $fillable = ['name', 'slug'];
    protected $appends = ['track_count', 'url'];
    protected $hidden = ['trackCountRelation'];

    public $timestamps = false;

    use SlugTrait, RevisionableTrait;

    public function tracks(){
        return $this->hasMany(Track::class, 'genre_id');
    }

    /**
     * "Dummy" relation to facilitate eager-loading of a genre's track count.
     * This relationship should not be used directly.
     *
     * Inspired by: http://laravel.io/forum/05-03-2014-eloquent-get-count-relation?page=1#reply-6226
     *
     * @return Relation
     */
    public function trackCountRelation() {
        return $this->hasOne(Track::class)
            ->select(['genre_id', DB::raw('count(*) as track_count')])
            ->groupBy('genre_id');
    }

    /**
     * Returns the number of tracks in this genre.
     *
     * @return int
     */
    public function getTrackCountAttribute() {
        return $this->trackCountRelation()->count();
    }

    /**
     * @return string relative, Angular-friendly URL to this genre
     */
    public function getUrlAttribute() {
        return URL::route('tracks.discover', ['filter' => "genres-{$this->id}"], false);
    }
}
