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

namespace Poniverse\Ponyfm\Http\Controllers\Api\Web;

use Auth;
use Carbon\Carbon;
use Input;
use Poniverse\Ponyfm\Http\Controllers\ApiControllerBase;
use Poniverse\Ponyfm\Models\Notification;

class NotificationsController extends ApiControllerBase
{
    /**
     * Returns the logged-in user's last 20 notifications.
     *
     * @return array
     */
    public function getNotifications()
    {
        $notifications = Notification::forUser(Auth::user())
            ->take(20)
            ->get();


        return ['notifications' => $notifications->toArray()];
    }

    /**
     * This action returns the number of notifications that were updated.
     * Any notifications that were specified that don't belong to the logged-in
     * user are ignored.
     *
     * @return array
     */
    public function putMarkAsRead()
    {
        $notificationIds = Input::get('notification_ids');
        $numberOfUpdatedRows = Auth::user()
            ->notifications()
            ->whereIn('id', $notificationIds)
            ->update(['is_read' => true]);

        return ['notifications_updated' => $numberOfUpdatedRows];
    }
}
