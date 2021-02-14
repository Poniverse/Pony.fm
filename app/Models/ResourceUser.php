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

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ResourceUser.
 *
 * @property int $id
 * @property int $user_id
 * @property int $track_id
 * @property int $album_id
 * @property int $playlist_id
 * @property int $artist_id
 * @property bool $is_followed
 * @property bool $is_favourited
 * @property bool $is_pinned
 * @property int $view_count
 * @property int $play_count
 * @property int $download_count
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ResourceUser whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ResourceUser whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ResourceUser whereTrackId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ResourceUser whereAlbumId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ResourceUser wherePlaylistId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ResourceUser whereArtistId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ResourceUser whereIsFollowed($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ResourceUser whereIsFavourited($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ResourceUser whereIsPinned($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ResourceUser whereViewCount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ResourceUser wherePlayCount($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ResourceUser whereDownloadCount($value)
 * @mixin \Eloquent
 */
class ResourceUser extends Model
{
    public $timestamps = false;

    public static function get($userId, $resourceType, $resourceId)
    {
        $resourceIdColumn = $resourceType.'_id';
        $existing = self::where($resourceIdColumn, '=', $resourceId)->where('user_id', '=', $userId)->first();
        if ($existing) {
            return $existing;
        }

        $item = new self();
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
