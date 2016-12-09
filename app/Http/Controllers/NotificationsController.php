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

namespace Poniverse\Ponyfm\Http\Controllers;

use Poniverse\Ponyfm\Models\Email;
use Poniverse\Ponyfm\Models\EmailClick;
use Poniverse\Ponyfm\Models\EmailSubscription;
use View;

class NotificationsController extends Controller {
    public function getEmailClick($emailKey) {
        $emailKey = decrypt($emailKey);
        /** @var Email $email */
        $email = Email::findOrFail($emailKey);

        $email->emailClicks()->create(['ip_address' => \Request::ip()]);

        return redirect($email->getActivity()->url);
    }

    public function getEmailUnsubscribe($subscriptionKey) {
        $subscriptionId = decrypt($subscriptionKey);
        $subscription = EmailSubscription::findOrFail($subscriptionId);

        return var_export($subscription);
    }
}
