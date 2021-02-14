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

namespace App\Http\Controllers;

use App;
use App\Models\Email;
use App\Models\EmailSubscription;
use Auth;
use DB;
use View;

class NotificationsController extends Controller
{
    /**
     * @param $emailKey
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function getEmailClick($emailKey)
    {
        /** @var Email $email */
        $email = Email::findOrFail($emailKey);

        DB::transaction(function () use ($email) {
            $email->emailClicks()->create(['ip_address' => \Request::ip()]);
            $email->notification->is_read = true;
            $email->notification->save();
        });

        return redirect($email->getActivity()->url);
    }

    public function getEmailUnsubscribe($subscriptionKey)
    {
        /** @var EmailSubscription $subscription */
        $subscription = EmailSubscription::findOrFail($subscriptionKey);
        $subscription->delete();

        if (Auth::check() && $subscription->user->id === Auth::user()->id) {
            return redirect(route('account:settings', [
                'slug' => $subscription->user->slug,
                'unsubscribedMessageKey' => $subscription->activity_type,
            ]), 303);
        } else {
            return redirect(route('email:confirm-unsubscribed', [
                'unsubscribedUser' => $subscription->user->display_name,
                'unsubscribedMessageKey' => $subscription->activity_type,
            ]), 303);
        }
    }

    public function getEmailUnsubscribePage()
    {
        return View::make('shared.null');
    }
}
