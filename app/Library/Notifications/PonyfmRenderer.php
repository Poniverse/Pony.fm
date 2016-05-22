<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Peter Deltchev
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

namespace Poniverse\Ponyfm\Library\Notifications;


use Poniverse\Ponyfm\Contracts\Favouritable;
use Poniverse\Ponyfm\Models\Comment;
use Poniverse\Ponyfm\Models\Playlist;
use Poniverse\Ponyfm\Models\Track;
use Poniverse\Ponyfm\Models\User;

class PonyfmRenderer {
    /**
     * @inheritdoc
     */
    public function publishedNewTrack(Track $track) {
    }

    /**
     * @inheritdoc
     */
    public function publishedNewPlaylist(Playlist $playlist) {
    }

    /**
     * @inheritdoc
     */
    public function newFollower(User $userBeingFollowed, User $follower) {
    }

    /**
     * @inheritdoc
     */
    public function newComment(Comment $comment) {
    }

    /**
     * @inheritdoc
     */
    public function newFavourite(Favouritable $entityBeingFavourited, User $favouriter) {
    }
}
