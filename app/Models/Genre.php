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
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Poniverse\Ponyfm\Traits\SlugTrait;
use Illuminate\Database\Eloquent\Model;
use Venturecraft\Revisionable\RevisionableTrait;

/**
 * Poniverse\Ponyfm\Models\Genre
 *
 * @property integer $id
 * @property string $name
 * @property string $slug
 * @property string $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\Poniverse\Ponyfm\Models\Track[] $tracks
 * @property-read \Poniverse\Ponyfm\Models\Track $trackCountRelation
 * @property-read mixed $track_count
 * @property-read mixed $url
 * @property-write mixed $title
 * @property-read \Illuminate\Database\Eloquent\Collection|\Venturecraft\Revisionable\Revision[] $revisionHistory
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Genre whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Genre whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Genre whereSlug($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Genre whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Genre whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Genre whereUpdatedAt($value)
 * @mixin \Eloquent
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Genre onlyTrashed()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Genre withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Genre withoutTrashed()
 */
class Genre extends Model
{
    protected $table = 'genres';

    protected $fillable = ['name', 'slug'];
    protected $appends = ['track_count', 'url'];
    protected $hidden = ['trackCountRelation'];

    use SlugTrait, SoftDeletes, RevisionableTrait;

    public function tracks()
    {
        return $this->hasMany(Track::class, 'genre_id');
    }

    /**
     * "Dummy" relation to facilitate eager-loading of a genre's track count.
     * This relationship should not be used directly.
     *
     * Inspired by {@link http://softonsofa.com/tweaking-eloquent-relations-how-to-get-hasmany-relation-count-efficiently/}
     *
     * @return Relation
     */
    public function trackCountRelation()
    {
        return $this->hasOne(Track::class)
            ->select(['genre_id', DB::raw('count(*) as track_count')])
            ->groupBy('genre_id');
    }

    /**
     * Returns the number of tracks in this genre.
     *
     * @return int
     */
    public function getTrackCountAttribute()
    {
        if (!$this->relationLoaded('trackCountRelation')) {
            $this->load('trackCountRelation');
        }

        return $this->trackCountRelation ? $this->trackCountRelation->track_count : 0;
    }

    /**
     * @return string relative, Angular-friendly URL to this genre
     */
    public function getUrlAttribute()
    {
        return route('tracks.discover', ['filter' => "genres-{$this->id}"], false);
    }
}
