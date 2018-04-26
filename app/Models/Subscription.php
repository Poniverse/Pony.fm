<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Logic
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

use Illuminate\Database\Eloquent\Model;

/**
 * Poniverse\Ponyfm\Models\Subscription
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $endpoint
 * @property string $p256dh
 * @property string $auth
 * @property-read \Poniverse\Ponyfm\Models\User $user
 * @property string $created_at
 * @property string $updated_at
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Subscription whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Subscription whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Subscription whereEndpoint($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Subscription whereP256dh($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Subscription whereAuth($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Subscription whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\Subscription whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Subscription extends Model
{
    public $timestamps = false;
    protected $fillable = ['user_id', 'endpoint', 'p256dh', 'auth'];
    protected $casts = [
        'id'          => 'integer',
        'user_id'     => 'integer',
        'endpoint'    => 'string',
        'p256dh'      => 'string',
        'auth'        => 'string'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
