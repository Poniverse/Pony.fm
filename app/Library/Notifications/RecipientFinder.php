<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Feld0
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

namespace App\Library\Notifications;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\DispatchesJobs;
use App\Contracts\Favouritable;
use App\Contracts\NotificationHandler;
use App\Jobs\SendNotifications;
use App\Library\Notifications\Drivers\NativeDriver;
use App\Library\Notifications\Drivers\PonyfmDriver;
use App\Models\Activity;
use App\Models\Comment;
use App\Models\Playlist;
use App\Models\Subscription;
use App\Models\Track;
use App\Models\User;

/**
 * Class RecipientFinder
 * @package App\Library\Notifications
 *
 * This class returns a list of users who are to receive a particular notification.
 * It is instantiated on a per-driver basis. Its methods return Eloquent query
 * objects for the PonyfmDriver.
 */
class RecipientFinder implements NotificationHandler
{
    /**
     * @var string class name of a notification driver
     */
    private $notificationDriver;
    
    public function __construct(string $notificationDriver)
    {
        $this->notificationDriver = $notificationDriver;
    }

    private function fail()
    {
        throw new \InvalidArgumentException("Unknown notification driver given: {$this->notificationDriver}");
    }

    /**
     * @inheritdoc
     */
    public function publishedNewTrack(Track $track)
    {
        switch ($this->notificationDriver) {
            case PonyfmDriver::class:
                return $track->user->followers();

            case NativeDriver::class:
                $followerIds = [];
                $subIds = [];
                $rawSubIds = Subscription::select('id')->get();

                foreach ($track->user->followers as $follower) {
                    array_push($followerIds, $follower->id);
                }

                foreach ($rawSubIds as $sub) {
                    array_push($subIds, $sub->id);
                }

                $targetIds = array_intersect($followerIds, $subIds);
                return Subscription::whereIn('user_id', $targetIds)->get();
            default:
                return $this->fail();
        }
    }

    /**
     * @inheritdoc
     */
    public function publishedNewPlaylist(Playlist $playlist)
    {
        switch ($this->notificationDriver) {
            case PonyfmDriver::class:
                return $playlist->user->followers();

            case NativeDriver::class:
                $followerIds = [];
                $subIds = [];
                $rawSubIds = Subscription::select('id')->get();

                foreach ($playlist->user->followers as $follower) {
                    array_push($followerIds, $follower->id);
                }

                foreach ($rawSubIds as $sub) {
                    array_push($subIds, $sub->id);
                }

                $targetIds = array_intersect($followerIds, $subIds);
                return Subscription::whereIn('user_id', $targetIds)->get();
            default:
                return $this->fail();
        }
    }

    /**
     * @inheritdoc
     */
    public function newFollower(User $userBeingFollowed, User $follower)
    {
        switch ($this->notificationDriver) {
            case PonyfmDriver::class:
                return $this->queryForUser($userBeingFollowed);

            case NativeDriver::class:
                return Subscription::where('user_id', '=', $userBeingFollowed->id)->get();
            default:
                return $this->fail();
        }
    }

    /**
     * @inheritdoc
     */
    public function newComment(Comment $comment)
    {
        switch ($this->notificationDriver) {
            case PonyfmDriver::class:
                return
                    $comment->user->id === $comment->resource->user->id
                        ? NULL
                        : $this->queryForUser($comment->resource->user);
            case NativeDriver::class:
                return Subscription::where('user_id', '=', $comment->resource->user->id)->get();
            default:
                return $this->fail();
        }
    }

    /**
     * @inheritdoc
     */
    public function newFavourite(Favouritable $entityBeingFavourited, User $favouriter)
    {
        switch ($this->notificationDriver) {
            case PonyfmDriver::class:
                return
                    $favouriter->id === $entityBeingFavourited->user->id
                        ? NULL
                        : $this->queryForUser($entityBeingFavourited->user);
            case NativeDriver::class:
                return Subscription::where('user_id', '=', $entityBeingFavourited->user->id)->get();
            default:
                return $this->fail();
        }
    }

    /**
     * Helper function that returns an Eloquent query instance that will return
     * a specific user when executed.
     *
     * @param User $user
     * @return \Eloquent|Builder
     */
    private function queryForUser(User $user):Builder {
        return User::where('id', '=', $user->id);
    }
}
