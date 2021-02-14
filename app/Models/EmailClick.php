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

namespace App\Models;

use Alsofronie\Uuid\UuidModelTrait;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\EmailClick.
 *
 * @property string $id
 * @property string $email_id
 * @property string $ip_address
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\Email $email
 * @method static \Illuminate\Database\Query\Builder|\App\Models\EmailClick whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\EmailClick whereEmailId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\EmailClick whereIpAddress($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\EmailClick whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\EmailClick whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class EmailClick extends Model
{
    use UuidModelTrait;

    protected $fillable = ['ip_address'];

    public function email()
    {
        return $this->belongsTo(Email::class, 'email_id', 'id', 'emails');
    }
}
