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

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Models\Email;
use App\Models\EmailSubscription;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

class NotificationsController extends Controller
{
    /**
     * @param $emailKey
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function getEmailClick(Request $request, $emailKey)
    {
        /** @var Email $email */
        $email = Email::findOrFail($emailKey);

        DB::transaction(function () use ($email) {
            $email->emailClicks()->create(['ip_address' => $request->ip()]);
            $email->notification->is_read = true;
            $email->notification->save();
        });

        return redirect()->to($email->getActivity()->url);
    }

    public function getEmailUnsubscribe(Request $request, $subscriptionKey)
    {
        /** @var EmailSubscription $subscription */
        $subscription = EmailSubscription::findOrFail($subscriptionKey);
        $subscription->delete();

        if ($request->user() && $subscription->user->id === $request->user()->id) {
            return redirect(route('account:settings', [
                'slug' => $subscription->user->slug,
                'unsubscribedMessageKey' => $subscription->activity_type,
            ]), );
        } else {
            return redirect(route('email:confirm-unsubscribed', [
                'unsubscribedUser' => $subscription->user->display_name,
                'unsubscribedMessageKey' => $subscription->activity_type,
            ]), );
        }
    }

    public function getEmailUnsubscribePage()
    {
        return view('shared.null');
    }
}
