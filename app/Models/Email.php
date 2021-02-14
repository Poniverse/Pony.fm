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

namespace Poniverse\Ponyfm\Models;

use Alsofronie\Uuid\UuidModelTrait;
use Illuminate\Database\Eloquent\Model;
use Poniverse\Ponyfm\Models\Notification;

/**
 * Poniverse\Ponyfm\Models\Email.
 *
 * @property string $id
 * @property int $notification_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Poniverse\Ponyfm\Models\Notification $notification
 * @property-read \Illuminate\Database\Eloquent\Collection|\Poniverse\Ponyfm\Models\EmailClick[] $emailClicks
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Email whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Email whereNotificationId($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Email whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Email whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Email extends Model
{
    use UuidModelTrait;
    // Non-sequential UUID's are desirable for this model.
    protected $uuidVersion = 4;

    public function notification()
    {
        return $this->belongsTo(Notification::class, 'notification_id', 'id', 'notifications');
    }

    public function emailClicks()
    {
        return $this->hasMany(EmailClick::class, 'email_id', 'id');
    }

    public function getActivity():Activity
    {
        return $this->notification->activity;
    }

    public function getUser():User
    {
        return $this->notification->recipient;
    }

    public function getSubscription():EmailSubscription
    {
        return $this
            ->getUser()
            ->emailSubscriptions()
            ->where('activity_type', $this->getActivity()->activity_type)
            ->first();
    }
}
