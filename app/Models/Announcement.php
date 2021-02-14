<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Logic.
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

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Announcement.
 *
 * @property int $id
 * @property string $title
 * @property string $text_content
 * @property int $announcement_type_id
 * @property mixed $links
 * @property mixed $tracks
 * @property string $css_class
 * @property string $template_file
 * @property string $start_time
 * @property string $end_time
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Announcement whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Announcement whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Announcement whereTextContent($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Announcement whereAnnouncementTypeId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Announcement whereLinks($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Announcement whereTracks($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Announcement whereCssClass($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Announcement whereTemplateFile($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Announcement whereStartTime($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Announcement whereEndTime($value)
 * @mixin \Eloquent
 */
class Announcement extends Model
{

    protected $casts = [
        'links' => 'array',
        'tracks' => 'array',
    ];

    const TYPE_GENERIC = 1;
    const TYPE_WARNING_ALERT = 2;
    const TYPE_SERIOUS_ALERT = 3;
    const TYPE_CUSTOM = 4;
}
