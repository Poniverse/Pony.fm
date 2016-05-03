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

namespace Poniverse\Ponyfm\Http\Controllers\Api\Web;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Poniverse\Ponyfm\Http\Controllers\ApiControllerBase;
use Poniverse\Ponyfm\Models\Track;
use Auth;
use DB;
use Response;
use Carbon\Carbon;

class StatsController extends ApiControllerBase
{
    public function getTrackStatsHourly($id)
    {
        // I know, raw SQL ugh. I'm not used to laravel's query system
        $query = DB::select(DB::raw('
            SELECT TIMESTAMP(created_at) AS `time`, COUNT(1) AS `plays`
            FROM `resource_log_items`
            WHERE `track_id` = :id AND `log_type` = 3 AND `created_at` > now() - INTERVAL 1 DAY
            GROUP BY TIMESTAMP(created_at);'), array(
            'id' => $id
        ));

        $now = Carbon::now();
        $calcArray = array();
        $output = array();

        foreach($query as $item) {
            $playDate = new Carbon($item->time);
            $key = '-' . $playDate->diffInHours($now);
            if (array_key_exists($key, $calcArray)) {
                $calcArray[$key] += $item->plays;
            } else {
                $calcArray[$key] = $item->plays;
            }
        }

        // Covert calcArray into output we can understand
        foreach($calcArray as $hour => $plays) {
            $set = array('hour' => $hour . 'hours', 'plays' => $plays);
            array_push($output, $set);
        }

        return Response::json(['playStats' => $output, 'type' => 'Hourly'], 200);
    }

    public function getTrackStatsDaily($id)
    {
        // I know, raw SQL ugh. I'm not used to laravel's query system
        // Only go back 1 month for daily stuff, may change in the future
        $query = DB::select(DB::raw('
            SELECT TIMESTAMP(created_at) AS `time`, COUNT(1) AS `plays`
            FROM `resource_log_items`
            WHERE `track_id` = :id AND `log_type` = 3 AND `created_at` > now() - INTERVAL 1 MONTH
            GROUP BY TIMESTAMP(created_at);'), array(
            'id' => $id
        ));

        $now = Carbon::now();
        $calcArray = array();
        $output = array();

        foreach($query as $item) {
            $playDate = new Carbon($item->time);
            $key = '-' . $playDate->diffInDays($now);
            if (array_key_exists($key, $calcArray)) {
                $calcArray[$key] += $item->plays;
            } else {
                $calcArray[$key] = $item->plays;
            }
        }

        // Covert calcArray into output we can understand
        foreach($calcArray as $days => $plays) {
            $set = array('days' => $days . ' days', 'plays' => $plays);
            array_push($output, $set);
        }

        return Response::json(['playStats' => $output, 'type' => 'Daily'], 200);
    }

    public function getTrackStats($id) {
        // Get track to check if it exists
        // and if we are allowed to view it.
        // In the future we could do something
        // with this data, not sure.
        try {
            $track = Track::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return $this->notFound('Track not found!');
        }

        // Do we have permission to view this track?
        if (!$track->canView(Auth::user()))
            return $this->notFound('Track not found!');

        // Run one of the functions depending on
        // how old the track is
        $now = Carbon::now();
        $trackDate = $track->published_at;

        if ($trackDate->diffInDays($now) >= 1) {
            return $this->getTrackStatsDaily($id);
        } else {
            return $this->getTrackStatsHourly($id);
        }
    }
}
