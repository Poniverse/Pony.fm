<?php

namespace Poniverse\Ponyfm;

use Illuminate\Database\Eloquent\Model;

class PinnedPlaylist extends Model
{
    protected $table = 'pinned_playlists';

    public function user()
    {
        return $this->belongsTo('Poniverse\Ponyfm\User');
    }

    public function playlist()
    {
        return $this->belongsTo('Poniverse\Ponyfm\Playlist');
    }
}