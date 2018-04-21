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

namespace Poniverse\Ponyfm\Models;

use Alsofronie\Uuid\UuidModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Poniverse\Ponyfm\Models\User;

/**
 * Poniverse\Ponyfm\EmailSubscription
 *
 * @property string $id
 * @property integer $user_id
 * @property integer $activity_type
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property-read \Poniverse\Ponyfm\Models\User $user
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\EmailSubscription whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\EmailSubscription whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\EmailSubscription whereActivityType($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\EmailSubscription whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\EmailSubscription whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\EmailSubscription whereDeletedAt($value)
 * @mixin \Eloquent
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\EmailSubscription onlyTrashed()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\EmailSubscription withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\EmailSubscription withoutTrashed()
 */
class EmailSubscription extends Model
{
    use UuidModelTrait, SoftDeletes;
    // Non-sequential UUID's are desirable for this model.
    protected $uuidVersion = 4;

    protected $fillable = ['activity_type'];

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id', 'users');
    }
}
