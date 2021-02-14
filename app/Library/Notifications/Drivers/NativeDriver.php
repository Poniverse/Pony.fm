<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Logic.
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

namespace App\Library\Notifications\Drivers;

use App\Contracts\Favouritable;
use App\Models\Activity;
use App\Models\Comment;
use App\Models\Playlist;
use App\Models\Track;
use App\Models\User;
use Config;
use Minishlink\WebPush\WebPush;

class NativeDriver extends AbstractDriver
{
    /**
     * Method for sending notifications to devices.
     *
     * @param Activity $activity
     * @param User[] $recipients collection of {@link User} objects
     */
    private function pushNotifications(Activity $activity, $recipients)
    {
        if (Config::get('ponyfm.gcm_key') != 'default') {
            $apiKeys = [
                'GCM' => Config::get('ponyfm.gcm_key'),
            ];

            $webPush = new WebPush($apiKeys);

            $data = [
                'id' => $activity->id,
                'text' => $activity->getTextAttribute(),
                'title' => $activity->getTitleFromActivityType(),
                'image' => $activity->getThumbnailUrlAttribute(),
                'url' => $activity->url,
            ];

            $jsonData = json_encode($data);

            foreach ($recipients as $recipient) {
                $webPush->sendNotification(
                    $recipient->endpoint,
                    $jsonData,
                    $recipient->p256dh,
                    $recipient->auth
                );
            }

            $webPush->flush();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function publishedNewTrack(Track $track)
    {
        $activity = Activity::where('user_id', $track->user_id)
            ->where('activity_type', Activity::TYPE_PUBLISHED_TRACK)
            ->where('resource_id', $track->id)
            ->get()[0];

        $this->pushNotifications($activity, $this->getRecipients(__FUNCTION__, func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    public function publishedNewPlaylist(Playlist $playlist)
    {
        $activity = Activity::where('user_id', $playlist->user_id)
            ->where('activity_type', Activity::TYPE_PUBLISHED_PLAYLIST)
            ->where('resource_id', $playlist->id)
            ->get()[0];

        $this->pushNotifications($activity, $this->getRecipients(__FUNCTION__, func_get_args()));
    }

    public function newFollower(User $userBeingFollowed, User $follower)
    {
        $activity = Activity::where('user_id', $follower->id)
            ->where('activity_type', Activity::TYPE_NEW_FOLLOWER)
            ->where('resource_id', $userBeingFollowed->id)
            ->get()[0];

        $this->pushNotifications($activity, $this->getRecipients(__FUNCTION__, func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    public function newComment(Comment $comment)
    {
        $activity = Activity::where('user_id', $comment->user_id)
            ->where('activity_type', Activity::TYPE_NEW_COMMENT)
            ->where('resource_id', $comment->id)
            ->get()[0];

        $this->pushNotifications($activity, $this->getRecipients(__FUNCTION__, func_get_args()));
    }

    /**
     * {@inheritdoc}
     */
    public function newFavourite(Favouritable $entityBeingFavourited, User $favouriter)
    {
        $activity = Activity::where('user_id', $favouriter->id)
            ->where('activity_type', Activity::TYPE_CONTENT_FAVOURITED)
            ->where('resource_id', $entityBeingFavourited->id)
            ->get()[0];

        $this->pushNotifications($activity, $this->getRecipients(__FUNCTION__, func_get_args()));
    }
}
