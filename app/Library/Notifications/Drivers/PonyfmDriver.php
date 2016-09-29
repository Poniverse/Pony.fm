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

namespace Poniverse\Ponyfm\Library\Notifications\Drivers;

use ArrayAccess;
use Carbon\Carbon;
use Poniverse\Ponyfm\Contracts\Favouritable;
use Poniverse\Ponyfm\Models\Activity;
use Poniverse\Ponyfm\Models\Comment;
use Poniverse\Ponyfm\Models\Notification;
use Poniverse\Ponyfm\Models\Playlist;
use Poniverse\Ponyfm\Models\Track;
use Poniverse\Ponyfm\Models\User;

class PonyfmDriver extends AbstractDriver
{
    /**
     * A helper method for bulk insertion of notification records.
     *
     * @param int $activityId
     * @param User[] $recipients collection of {@link User} objects
     */
    private function insertNotifications(int $activityId, $recipients)
    {
        $notifications = [];
        foreach ($recipients as $recipient) {
            $notifications[] = [
                'activity_id'   => $activityId,
                'user_id'       => $recipient->id
            ];
        }
        Notification::insert($notifications);
    }

    /**
     * @inheritdoc
     */
    public function publishedNewTrack(Track $track)
    {
        $activity = Activity::create([
            'created_at'    => Carbon::now(),
            'user_id'       => $track->user_id,
            'activity_type' => Activity::TYPE_PUBLISHED_TRACK,
            'resource_type' => Track::class,
            'resource_id'   => $track->id,
        ]);

        $this->insertNotifications($activity->id, $this->getRecipients(__FUNCTION__, func_get_args()));
    }

    /**
     * @inheritdoc
     */
    public function publishedNewPlaylist(Playlist $playlist)
    {
        $activity = Activity::create([
            'created_at' => Carbon::now(),
            'user_id' => $playlist->user_id,
            'activity_type' => Activity::TYPE_PUBLISHED_PLAYLIST,
            'resource_type' => Playlist::class,
            'resource_id' => $playlist->id,
        ]);

        $this->insertNotifications($activity->id, $this->getRecipients(__FUNCTION__, func_get_args()));
    }

    public function newFollower(User $userBeingFollowed, User $follower)
    {
        $activity = Activity::create([
            'created_at' => Carbon::now(),
            'user_id' => $follower->id,
            'activity_type' => Activity::TYPE_NEW_FOLLOWER,
            'resource_type' => User::class,
            'resource_id' => $userBeingFollowed->id,
        ]);

        $this->insertNotifications($activity->id, $this->getRecipients(__FUNCTION__, func_get_args()));
    }

    /**
     * @inheritdoc
     */
    public function newComment(Comment $comment)
    {
        $activity = Activity::create([
            'created_at' => Carbon::now(),
            'user_id' => $comment->user_id,
            'activity_type' => Activity::TYPE_NEW_COMMENT,
            'resource_type' => Comment::class,
            'resource_id' => $comment->id,
        ]);

        $this->insertNotifications($activity->id, $this->getRecipients(__FUNCTION__, func_get_args()));
    }

    /**
     * @inheritdoc
     */
    public function newFavourite(Favouritable $entityBeingFavourited, User $favouriter)
    {
        $activity = Activity::create([
            'created_at' => Carbon::now(),
            'user_id' => $favouriter->id,
            'activity_type' => Activity::TYPE_CONTENT_FAVOURITED,
            'resource_type' => get_class($entityBeingFavourited),
            'resource_id' => $entityBeingFavourited->id,
        ]);

        $this->insertNotifications($activity->id, $this->getRecipients(__FUNCTION__, func_get_args()));
    }
}
