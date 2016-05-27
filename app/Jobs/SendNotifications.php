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

namespace Poniverse\Ponyfm\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Poniverse\Ponyfm\Jobs\Job;
use Illuminate\Contracts\Bus\SelfHandling;
use Poniverse\Ponyfm\Library\Notifications\Drivers\AbstractDriver;
use Poniverse\Ponyfm\Library\Notifications\Drivers\PonyfmDriver;
use Poniverse\Ponyfm\Models\User;
use SerializesModels;

class SendNotifications extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $notificationType;
    protected $notificationData;

    /**
     * Create a new job instance.
     * @param string $notificationType
     * @param array $notificationData
     */
    public function __construct(string $notificationType, array $notificationData)
    {
        $this->notificationType = $notificationType;
        $this->notificationData = $notificationData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->beforeHandle();
        
        // get list of users who are supposed to receive this notification
        $recipients = [User::find(1)];

        foreach ($recipients as $recipient) {
            // get drivers that this notification should be delivered through
            $drivers = $this->getDriversForNotification($recipient, $this->notificationType);

            foreach ($drivers as $driver) {
                /** @var $driver AbstractDriver */
                call_user_func_array([$driver, $this->notificationType], $this->notificationData);
            }
        }
    }

    /**
     * Returns the drivers with which the given user has subscribed to the given
     * notification type.
     *
     * @param User $user
     * @param string $notificationType
     * @return AbstractDriver[]
     */
    private function getDriversForNotification(User $user, string $notificationType) {
        return [new PonyfmDriver()];
    }
}
