<?php

use Illuminate\Database\Eloquent\Model;

class PinnedPlaylist extends Model
{
    protected $table = 'pinned_playlists';

    public function user()
    {
        return $this->belongsTo('User');
    }

    public function playlist()
    {
        return $this->belongsTo('Playlist');
    }
}