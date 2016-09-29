<?php

namespace Poniverse\Ponyfm\Policies;

use Poniverse\Ponyfm\Models\ShowSong;
use Poniverse\Ponyfm\Models\User;

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
