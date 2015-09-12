<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Favourite extends Model
{
    protected $table = 'favourites';
    public $timestamps = false;

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function track()
    {
        return $this->belongsTo('App\Track');
    }

    public function album()
    {
        return $this->belongsTo('App\Album');
    }

    public function playlist()
    {
        return $this->belongsTo('App\Playlist');
    }

    /**
     * Return the resource associated with this favourite.
     *
     * @return Resource|NULL
     */
    public function getResourceAttribute()
    {
        if ($this->track_id) {
            return $this->track;
        } else {
            if ($this->album_id) {
                return $this->album;
            } else {
                if ($this->playlist_id) {
                    return $this->playlist;
                } // no resource - this should never happen under real circumstances
                else {
                    return null;
                }
            }
        }
    }

    public function getTypeAttribute()
    {
        return get_class($this->resource);
    }
}