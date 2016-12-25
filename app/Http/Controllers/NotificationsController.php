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

use App;
use DB;
use Poniverse\Ponyfm\Models\Email;
use Poniverse\Ponyfm\Models\EmailSubscription;


class NotificationsController extends Controller {
    /**
     * @param $emailKey
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function getEmailClick($emailKey) {
        /** @var Email $email */
        $email = Email::findOrFail($emailKey);

        DB::transaction(function() use ($email) {
            $email->emailClicks()->create(['ip_address' => \Request::ip()]);
            $email->notification->is_read = true;
            $email->notification->save();
        });

        return redirect($email->getActivity()->url);
    }

    public function getEmailUnsubscribe($subscriptionKey) {
        $subscription = EmailSubscription::findOrFail($subscriptionKey);
        $subscription->delete();

        return 'Unsubscribed!';
    }
}
