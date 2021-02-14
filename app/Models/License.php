<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Feld0.
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
 * Poniverse\Ponyfm\Models\License.
 *
 * @property int $id
 * @property string $title
 * @property string $description
 * @property bool $affiliate_distribution
 * @property bool $open_distribution
 * @property bool $remix
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\License whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\License whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\License whereDescription($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\License whereAffiliateDistribution($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\License whereOpenDistribution($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\License whereRemix($value)
 * @mixin \Eloquent
 */
class License extends Model
{
    protected $table = 'licenses';
}
