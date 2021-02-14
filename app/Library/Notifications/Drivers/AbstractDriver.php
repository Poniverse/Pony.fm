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

use App\Contracts\NotificationHandler;
use App\Library\Notifications\RecipientFinder;
use App\Models\User;
use ArrayAccess;

abstract class AbstractDriver implements NotificationHandler
{
    private $recipientFinder;

    public function __construct()
    {
        $notificationDriverClass = get_class($this);

        switch ($notificationDriverClass) {
            case EmailDriver::class:
            case PonyfmDriver::class:
                $this->recipientFinder = new RecipientFinder(get_class($this));
                break;
            default:
                throw new \Exception('Invalid notification driver!');
        }
    }

    /**
     * Returns an array of users who are to receive the given notification type.
     * This method is a wrapper around the {@link RecipientFinder} class, which
     * does the actual processing for all the drivers.
     *
     * @param string $notificationType
     * @param array $notificationData
     * @return User[] collection of {@link User} objects
     */
    protected function getRecipients(string $notificationType, array $notificationData)
    {
        return call_user_func_array([$this->recipientFinder, $notificationType], $notificationData);
    }
}
