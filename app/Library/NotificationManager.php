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

namespace Poniverse\Ponyfm\Library;


use Illuminate\Foundation\Bus\DispatchesJobs;
use Poniverse\Ponyfm\Contracts\Favouritable;
use Poniverse\Ponyfm\Contracts\NotificationHandler;
use Poniverse\Ponyfm\Jobs\SendNotifications;
use Poniverse\Ponyfm\Models\Comment;
use Poniverse\Ponyfm\Models\Playlist;
use Poniverse\Ponyfm\Models\Track;
use Poniverse\Ponyfm\Models\User;

/**
 * Class NotificationManager
 * @package Poniverse\Ponyfm\Library
 *
 * This class exists mostly to maintain type safety when sending notifications
 * from around the Pony.fm codebase. There should be virtually zero logic here.
 * All the heavy lifting happens asynchronously in the {@link SendNotifications}
 * job and the notification drivers.
 */
class NotificationManager implements NotificationHandler {
    use DispatchesJobs;

    private function dispatchNotification(string $notificationType, array $notificationData) {
        $this->dispatch(new SendNotifications($notificationType, $notificationData));
    }

    /**
     * @param Track $track
     */
    public function publishedNewTrack(Track $track):void {
        $this->dispatchNotification(__FUNCTION__, func_get_args());
    }

    /**
     * @param Playlist $playlist
     */
    public function publishedNewPlaylist(Playlist $playlist):void {
        $this->dispatchNotification(__FUNCTION__, func_get_args());
    }

    /**
     * @param User $userBeingFollowed
     * @param User $follower
     */
    public function newFollower(User $userBeingFollowed, User $follower):void {
        $this->dispatchNotification(__FUNCTION__, func_get_args());
    }

    /**
     * @param Comment $comment
     */
    public function newComment(Comment $comment):void {
        $this->dispatchNotification(__FUNCTION__, func_get_args());
    }

    /**
     * @param Favouritable $entityBeingFavourited
     * @param User $favouriter
     */
    public function newFavourite(Favouritable $entityBeingFavourited, User $favouriter):void {
        $this->dispatchNotification(__FUNCTION__, func_get_args());
    }
}
