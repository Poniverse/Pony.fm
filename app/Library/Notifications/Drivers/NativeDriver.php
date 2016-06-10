<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Josef Citrine
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


use Carbon\Carbon;
use Poniverse\Ponyfm\Contracts\Favouritable;
use Poniverse\Ponyfm\Models\Activity;
use Poniverse\Ponyfm\Models\Comment;
use Poniverse\Ponyfm\Models\Notification;
use Poniverse\Ponyfm\Models\Playlist;
use Poniverse\Ponyfm\Models\Track;
use Poniverse\Ponyfm\Models\User;

class NativeDriver extends AbstractDriver {
    /**
     * A helper method for bulk insertion of notification records.
     *
     * @param int $activityId
     * @param User[] $recipients collection of {@link User} objects
     */
    private function pushNotifications(int $activityId, $recipients) {
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
    public function publishedNewTrack(Track $track) {
        $activity = Activity::where('user_id', $track->user_id)
            ->where('activity_type', Activity::TYPE_PUBLISHED_TRACK)
            ->where('resource_id', $track->id)
            ->get()[0];


        $this->pushNotifications($activity->id, $this->getRecipients(__FUNCTION__, func_get_args()));
    }

    /**
     * @inheritdoc
     */
    public function publishedNewPlaylist(Playlist $playlist) {
        $activity = Activity::where('user_id', $playlist->user_id)
            ->where('activity_type', Activity::TYPE_PUBLISHED_PLAYLIST)
            ->where('resource_id', $playlist->id)
            ->get()[0];

        $this->pushNotifications($activity->id, $this->getRecipients(__FUNCTION__, func_get_args()));
    }

    public function newFollower(User $userBeingFollowed, User $follower) {
        $activity = Activity::where('user_id', $follower->user_id)
            ->where('activity_type', Activity::TYPE_NEW_FOLLOWER)
            ->where('resource_id', $userBeingFollowed->id)
            ->get()[0];

        $this->pushNotifications($activity->id, $this->getRecipients(__FUNCTION__, func_get_args()));
    }

    /**
     * @inheritdoc
     */
    public function newComment(Comment $comment) {
        $activity = Activity::where('user_id', $comment->user_id)
            ->where('activity_type', Activity::TYPE_NEW_COMMENT)
            ->where('resource_id', $comment->id)
            ->get()[0];

        $this->pushNotifications($activity->id, $this->getRecipients(__FUNCTION__, func_get_args()));
    }

    /**
     * @inheritdoc
     */
    public function newFavourite(Favouritable $entityBeingFavourited, User $favouriter) {
        $activity = Activity::where('user_id', $favouriter->user_id)
            ->where('activity_type', Activity::TYPE_CONTENT_FAVOURITED)
            ->where('resource_id', $entityBeingFavourited->id)
            ->get()[0];

        $this->pushNotifications($activity->id, $this->getRecipients(__FUNCTION__, func_get_args()));
    }
}
