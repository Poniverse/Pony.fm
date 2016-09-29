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

namespace Poniverse\Ponyfm\Policies;

use Poniverse\Ponyfm\Models\Track;
use Poniverse\Ponyfm\Models\User;

class TrackPolicy
{
    public function edit(User $user, Track $track)
    {
        return $user->id === $track->user_id || $user->hasRole('admin');
    }

    public function delete(User $user, Track $track)
    {
        return $user->id === $track->user_id || $user->hasRole('admin');
    }
}
