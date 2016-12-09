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

namespace Poniverse\Ponyfm\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Poniverse\Ponyfm\Models\Email;

abstract class BaseNotification extends Mailable {
    use Queueable, SerializesModels;

    /** @var Email */
    protected $emailRecord;

    /** @var \Poniverse\Ponyfm\Models\Notification */
    protected $notificationRecord;

    /** @var \Poniverse\Ponyfm\Models\Activity */
    protected $activityRecord;

    /** @var \Poniverse\Ponyfm\Models\User */
    protected $initiatingUser;

    /**
     * Create a new message instance.
     *
     * @param Email $email
     */
    public function __construct(Email $email) {
        $this->emailRecord = $email;
        $this->notificationRecord = $email->notification;
        $this->activityRecord = $email->notification->activity;
        $this->initiatingUser = $email->notification->activity->initiatingUser;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    abstract public function build();

    /**
     * Generates an unsubscribe URL unique to the user.
     *
     * @return string
     */
    protected function generateUnsubscribeUrl() {
        $subscriptionKey = encrypt($this->emailRecord->getSubscription()->id);
        return route('email:unsubscribe', ['subscriptionKey' => $subscriptionKey]);
    }

    /**
     * Generates a trackable URL to the content item this email is about.
     *
     * @return string
     */
    protected function generateNotificationUrl() {
        $emailKey = encrypt($this->emailRecord->id);
        return route('email:click', ['emailKey' => $emailKey]);
    }

    /**
     * Helper method to eliminate duplication between different types of notifications.
     * Use it inside the build() method on this class's children.
     *
     * @param string $templateName
     * @param string $subject
     * @param array $extraVariables
     * @return $this
     */
    protected function renderEmail(string $templateName, string $subject, array $extraVariables) {
        return $this
            ->subject($subject)
            ->view("emails.{$templateName}")
            ->text("emails.{$templateName}_plaintext")
            ->with(array_merge($extraVariables, [
                'notificationUrl' => $this->generateNotificationUrl(),
                'unsubscribeUrl' => $this->generateUnsubscribeUrl()
            ]));
    }
}
