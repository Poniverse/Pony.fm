<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Feld0
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
 * Poniverse\Ponyfm\Models\TrackType
 *
 * @property integer $id
 * @property string $title
 * @property string $editor_title
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\TrackType whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\TrackType whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\Poniverse\Ponyfm\Models\TrackType whereEditorTitle($value)
 * @mixin \Eloquent
 */
class TrackType extends Model
{
    protected $table = 'track_types';

    const ORIGINAL_TRACK = 1;
    const OFFICIAL_TRACK_REMIX = 2;
    const FAN_TRACK_REMIX = 3;
    const PONIFIED_TRACK = 4;
    const OFFICIAL_AUDIO_REMIX = 5;
    const UNCLASSIFIED_TRACK = 6;
}
