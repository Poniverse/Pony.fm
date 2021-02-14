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

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Auth;
use DB;
use Request;

/**
 * App\Models\ResourceLogItem
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $log_type
 * @property string $ip_address
 * @property integer $track_format_id
 * @property integer $track_id
 * @property integer $album_id
 * @property integer $playlist_id
 * @property \Carbon\Carbon $created_at
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ResourceLogItem whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ResourceLogItem whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ResourceLogItem whereLogType($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ResourceLogItem whereIpAddress($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ResourceLogItem whereTrackFormatId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ResourceLogItem whereTrackId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ResourceLogItem whereAlbumId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ResourceLogItem wherePlaylistId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\ResourceLogItem whereCreatedAt($value)
 * @mixin \Eloquent
 */
class ResourceLogItem extends Model
{
    protected $table = 'resource_log_items';
    public $timestamps = false;
    protected $dates = ['created_at'];

    const VIEW = 1;
    const DOWNLOAD = 2;
    const PLAY = 3;

    public static function logItem($resourceType, $resourceId, $logType, $formatId = null)
    {
        $resourceIdColumn = $resourceType.'_id';

        $logItem = new ResourceLogItem();
        $logItem->{$resourceIdColumn} = $resourceId;
        $logItem->created_at = Carbon::now();
        $logItem->log_type = $logType;
        $logItem->track_format_id = $formatId;
        $logItem->ip_address = Request::getClientIp();

        if (Auth::check()) {
            $logItem->user_id = Auth::user()->id;
        }

        $logItem->save();

        $resourceTable = $resourceType.'s';
        $countColumn = '';

        if ($logType == self::VIEW) {
            $countColumn = 'view_count';
        } else {
            if ($logType == self::DOWNLOAD) {
                $countColumn = 'download_count';
            } else {
                if ($logType == self::PLAY) {
                    $countColumn = 'play_count';
                }
            }
        }

        // We do this to prevent a race condition. Sure I could simply increment the count columns and re-save back to the db
        // but that would require an additional SELECT and the operation would be non-atomic. If two log items are created
        // for the same resource at the same time, the cached values will still be correct with this method.

        DB::table($resourceTable)->whereId($resourceId)->update([
            $countColumn =>
                DB::raw('(SELECT
                    COUNT(id)
                FROM
                    resource_log_items
                WHERE ' .
                    $resourceIdColumn.' = '.$resourceId.'
                AND
                    log_type = ' . $logType.')')
        ]);

        if (Auth::check()) {
            $resourceUserId = ResourceUser::getId(Auth::user()->id, $resourceType, $resourceId);
            DB::table('resource_users')->whereId($resourceUserId)->update([
                $countColumn =>
                    DB::raw('(SELECT
                        COUNT(id)
                    FROM
                        resource_log_items
                    WHERE
                        user_id = ' . Auth::user()->id.'
                    AND ' .
                        $resourceIdColumn.' = '.$resourceId.'
                    AND
                        log_type = ' . $logType.')')
            ]);
        }
    }
}
