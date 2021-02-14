<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Feld0.
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

namespace Poniverse\Ponyfm\Contracts;

use Poniverse\Ponyfm\Models\Comment;
use Poniverse\Ponyfm\Models\Playlist;
use Poniverse\Ponyfm\Models\Track;
use Poniverse\Ponyfm\Models\User;

/**
 * Interface NotificationHandler.
 */
interface NotificationHandler
{
    /**
     * @param Track $track
     * @return void
     */
    public function publishedNewTrack(Track $track);

    /**
     * @param Playlist $playlist
     * @return void
     */
    public function publishedNewPlaylist(Playlist $playlist);

    /**
     * @param User $userBeingFollowed
     * @param User $follower
     * @return void
     */
    public function newFollower(User $userBeingFollowed, User $follower);

    /**
     * @param Comment $comment
     * @return void
     */
    public function newComment(Comment $comment);

    /**
     * @param Favouritable $entityBeingFavourited
     * @param User $favouriter
     * @return void
     */
    public function newFavourite(Favouritable $entityBeingFavourited, User $favouriter);
}
