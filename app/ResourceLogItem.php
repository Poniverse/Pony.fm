<?php

namespace Poniverse\Ponyfm;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Auth;
use DB;
use Request;

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
        $resourceIdColumn = $resourceType . '_id';

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

        $resourceTable = $resourceType . 's';
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
                    $resourceIdColumn . ' = ' . $resourceId . '
                AND
                    log_type = ' . $logType . ')')
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
                        user_id = ' . Auth::user()->id . '
                    AND ' .
                        $resourceIdColumn . ' = ' . $resourceId . '
                    AND
                        log_type = ' . $logType . ')')
            ]);
        }
    }
}
