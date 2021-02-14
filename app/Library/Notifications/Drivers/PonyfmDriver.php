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

namespace App\Library\Notifications\Drivers;

use App\Contracts\Favouritable;
use App\Mail\BaseNotification;
use App\Models\Activity;
use App\Models\Comment;
use App\Models\Email;
use App\Models\Notification;
use App\Models\Playlist;
use App\Models\Track;
use App\Models\User;
use Carbon\Carbon;
use Log;
use Mail;

class PonyfmDriver extends AbstractDriver
{
    /**
     * A helper method for bulk insertion of notification records.
     *
     * @param Activity $activity
     * @param User[] $recipients collection of {@link User} objects
     */
    private function insertNotifications(Activity $activity, $recipients)
    {
        $notifications = [];
        foreach ($recipients as $recipient) {
            $notifications[] = [
                'activity_id'   => $activity->id,
                'user_id'       => $recipient->id,
            ];
        }
        Notification::insert($notifications);
    }

    /**
     * Sends out an email about the given activity to the given set of users.
     *
     * @param Activity $activity
     * @param User[] $recipients collection of {@link User} objects
     */
    private function sendEmails(Activity $activity, $recipients)
    {
        foreach ($recipients as $recipient) {
            /** @var Notification $notification */
            $notification = $activity->notifications->where('user_id', $recipient->id)->first();
            /** @var Email $email */
            $email = $notification->email()->create([]);

            Log::debug("Attempting to send an email about notification {$notification->id} to {$recipient->email}.");
            Mail::to($recipient->email)->queue(BaseNotification::factory($activity, $email));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function publishedNewTrack(Track $track)
    {
        // Grabbing a fresh copy of the track here ensures that, if cover art
        // was changed from the default, that the updated cover art is used
        // in notification emails.
        $track = $track->fresh();

        $activity = Activity::create([
            'created_at'    => Carbon::now(),
            'user_id'       => $track->user_id,
            'activity_type' => Activity::TYPE_PUBLISHED_TRACK,
            'resource_type' => Track::class,
            'resource_id'   => $track->id,
        ]);

        $recipientsQuery = $this->getRecipients(__FUNCTION__, func_get_args());
        if (null !== $recipientsQuery) {
            $this->insertNotifications($activity, $recipientsQuery->get());
            $this->sendEmails($activity, $recipientsQuery->withEmailSubscriptionFor(Activity::TYPE_PUBLISHED_TRACK)->get());
        }
    }

    /**
     * {@inheritdoc}
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

        $recipientsQuery = $this->getRecipients(__FUNCTION__, func_get_args());
        if (null !== $recipientsQuery) {
            $this->insertNotifications($activity, $recipientsQuery->get());
            $this->sendEmails($activity, $recipientsQuery->withEmailSubscriptionFor(Activity::TYPE_PUBLISHED_PLAYLIST)->get());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function newFollower(User $userBeingFollowed, User $follower)
    {
        $activity = Activity::create([
            'created_at' => Carbon::now(),
            'user_id' => $follower->id,
            'activity_type' => Activity::TYPE_NEW_FOLLOWER,
            'resource_type' => User::class,
            'resource_id' => $userBeingFollowed->id,
        ]);

        $recipientsQuery = $this->getRecipients(__FUNCTION__, func_get_args());
        if (null !== $recipientsQuery) {
            $this->insertNotifications($activity, $recipientsQuery->get());
            $this->sendEmails($activity, $recipientsQuery->withEmailSubscriptionFor(Activity::TYPE_NEW_FOLLOWER)->get());
        }
    }

    /**
     * {@inheritdoc}
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

        $recipientsQuery = $this->getRecipients(__FUNCTION__, func_get_args());
        if (null !== $recipientsQuery) {
            $this->insertNotifications($activity, $recipientsQuery->get());
            $this->sendEmails($activity, $recipientsQuery->withEmailSubscriptionFor(Activity::TYPE_NEW_COMMENT)->get());
        }
    }

    /**
     * {@inheritdoc}
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

        $recipientsQuery = $this->getRecipients(__FUNCTION__, func_get_args());
        if (null !== $recipientsQuery) {
            $this->insertNotifications($activity, $recipientsQuery->get());
            $this->sendEmails($activity, $recipientsQuery->withEmailSubscriptionFor(Activity::TYPE_CONTENT_FAVOURITED)->get());
        }
    }
}
