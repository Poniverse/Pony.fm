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

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Activity;
use App\Models\Email;

abstract class BaseNotification extends Mailable {
    use Queueable, SerializesModels;

    /** @var Email */
    protected $emailRecord;

    /** @var \App\Models\Notification */
    protected $notificationRecord;

    /** @var \App\Models\Activity */
    protected $activityRecord;

    /** @var \App\Models\User */
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
     * Factory method that instantiates the appropriate {@link BaseNotification}
     * subclass for the given activity type and {@link Email} record.
     *
     * @param Activity $activity
     * @param Email $email
     * @return BaseNotification
     */
    static public function factory(Activity $activity, Email $email): BaseNotification {
        switch ($activity->activity_type) {
            case Activity::TYPE_NEWS:
                break;
            case Activity::TYPE_PUBLISHED_TRACK:
                return new NewTrack($email);
            case Activity::TYPE_PUBLISHED_ALBUM:
                break;
            case Activity::TYPE_PUBLISHED_PLAYLIST:
                return new NewPlaylist($email);
            case Activity::TYPE_NEW_FOLLOWER:
                return new NewFollower($email);
            case Activity::TYPE_NEW_COMMENT:
                return new NewComment($email);
            case Activity::TYPE_CONTENT_FAVOURITED:
                return new ContentFavourited($email);
            default:
                break;
        }
        throw new \InvalidArgumentException("Email notifications for activity type {$activity->activity_type} are not implemented!");
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
        return route('email:unsubscribe', ['subscriptionKey' => $this->emailRecord->getSubscription()->id]);
    }

    /**
     * Generates a trackable URL to the content item this email is about.
     *
     * @return string
     */
    protected function generateNotificationUrl() {
        return route('email:click', ['emailKey' => $this->emailRecord->id]);
    }

    /**
     * Helper method to eliminate duplication between different types of
     * notifications. Use it inside the build() method on this class's children.
     *
     * Note that data common to all notification types is merged into the
     * template variable array.
     *
     * @param string $templateName
     * @param string $subject
     * @param array $extraVariables
     * @return $this
     */
    protected function renderEmail(string $templateName, string $subject, array $extraVariables) {
        return $this
            ->subject($subject)
            ->view("emails.html.notifications.{$templateName}")
            ->text("emails.plaintext.notifications.{$templateName}")
            ->with(array_merge($extraVariables, [
                'notificationUrl'       => $this->generateNotificationUrl(),
                'unsubscribeUrl'        => $this->generateUnsubscribeUrl(),
                'thumbnailUrl'          => $this->activityRecord->thumbnail_url,
                'recipientName'         => $this->emailRecord->getUser()->display_name,
                'accountSettingsUrl'    => $this->emailRecord->getUser()->getSettingsUrl(),
                'replyEmailAddress'     => config('mail.from.address'),
                'currentYear'           => Carbon::now()->year,
            ]));
    }
}
