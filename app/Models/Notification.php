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

namespace Poniverse\Ponyfm\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Poniverse\Ponyfm\Models\Notification
 *
 * @property integer $id
 * @property integer $activity_id
 * @property integer $user_id
 * @property boolean $is_read
 * @property-read \Poniverse\Ponyfm\Models\Activity $activity
 * @property-read \Poniverse\Ponyfm\Models\User $recipient
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Notification forUser($user)
 */
class Notification extends Model {
    public $timestamps = false;
    protected $fillable = ['activity_id', 'user_id'];
    protected $casts = [
        'id'            => 'integer',
        'activity_id'   => 'integer',
        'user_id'       => 'integer',
        'is_read'       => 'boolean',
    ];

    public function activity() {
        return $this->belongsTo(Activity::class, 'activity_id', 'id');
    }
    
    public function recipient() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * This scope grabs eager-loaded notifications for the given user.
     *
     * @param Builder $query
     * @param User $user
     * @return Builder
     */
    public function scopeForUser(Builder $query, User $user) {
        $result = $query->with([
            'activity',
            'activity.initiatingUser',
            'activity.resource',
            'activity.resource.user',
        ])
            ->join('activities', 'notifications.activity_id', '=', 'activities.id')
            ->where('notifications.user_id', $user->id)
            ->whereNull('activities.deleted_at')
            ->select('*', 'notifications.id as id')
            ->orderBy('activities.created_at', 'DESC');

        return $result;
    }

    public function toArray() {
        if (is_null($this->activity->resource)) {
            return '';
        }

        return [
            'id'            => $this->id,
            'date'          => $this->activity->created_at->toAtomString(),
            'thumbnail_url' => $this->activity->thumbnail_url,
            'text'          => $this->activity->text,
            'url'           => $this->activity->url,
            'is_read'       => $this->is_read
        ];
    }
}
