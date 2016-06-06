<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Peter Deltchev
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
 * Poniverse\Ponyfm\Models\ResourceUser
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $track_id
 * @property integer $album_id
 * @property integer $playlist_id
 * @property integer $artist_id
 * @property boolean $is_followed
 * @property boolean $is_favourited
 * @property boolean $is_pinned
 * @property integer $view_count
 * @property integer $play_count
 * @property integer $download_count
 */
class ResourceUser extends Model
{
    protected $table = 'resource_users';
    public $timestamps = false;

    public static function get($userId, $resourceType, $resourceId)
    {
        $resourceIdColumn = $resourceType.'_id';
        $existing = self::where($resourceIdColumn, '=', $resourceId)->where('user_id', '=', $userId)->first();
        if ($existing) {
            return $existing;
        }

        $item = new ResourceUser();
        $item->{$resourceIdColumn} = $resourceId;
        $item->user_id = $userId;

        return $item;
    }

    public static function getId($userId, $resourceType, $resourceId)
    {
        $item = self::get($userId, $resourceType, $resourceId);
        if ($item->exists) {
            return $item->id;
        }

        $item->save();

        return $item->id;
    }
}
