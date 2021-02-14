<?php

namespace App\Policies;

use App\Models\ShowSong;
use App\Models\User;

class ShowSongPolicy
{
    public function rename(User $user, ShowSong $song)
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, ShowSong $song)
    {
        return $user->hasRole('admin');
    }
}
