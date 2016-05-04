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
        $query = DB::table('resource_log_items')
            ->selectRaw('created_at AS time, COUNT(1) AS `plays`')
            ->where('track_id', '=', $id)
            ->where('log_type', '=', 3)
            ->whereRaw('`created_at` > now() - INTERVAL 1 DAY')
            ->groupBy('created_at')
            ->get();

        $now = Carbon::now();
        $calcArray = array();
        $output = array();

        foreach($query as $item) {
            $playDate = new Carbon($item->time);
            $key = $playDate->diffInHours($now);
            if (array_key_exists($key, $calcArray)) {
                $calcArray[$key] += $item->plays;
            } else {
                $calcArray[$key] = $item->plays;
            }
        }

        // Get the first key in the array (oldest play)
        reset($calcArray);
        $lastKey = (int) key($calcArray);

        for ($i = 0; $i < $lastKey; $i++) {
            if (!isset($calcArray[$i])) {
                $calcArray[$i] = 0;
            }
        }

        krsort($calcArray);

        // Covert calcArray into output we can understand
        foreach($calcArray as $hour => $plays) {
            $set = [
                'hour' => $hour . ' ' . str_plural('hour', $hour),
                'plays' => $plays
            ];
            array_push($output, $set);
        }

        return Response::json(['playStats' => $output, 'type' => 'Hourly'], 200);
    }

    public function getTrackStatsDaily($id)
    {
        $query = DB::table('resource_log_items')
            ->selectRaw('created_at AS time, COUNT(1) AS `plays`')
            ->where('track_id', '=', $id)
            ->where('log_type', '=', 3)
            ->whereRaw('`created_at` > now() - INTERVAL 1 MONTH')
            ->groupBy('created_at')
            ->get();

        $now = Carbon::now();
        $calcArray = array();
        $output = array();

        foreach($query as $item) {
            $playDate = new Carbon($item->time);
            $key = $playDate->diffInDays($now);
            if (array_key_exists($key, $calcArray)) {
                $calcArray[$key] += $item->plays;
            } else {
                $calcArray[$key] = $item->plays;
            }
        }

        // Get the first key in the array (oldest play)
        reset($calcArray);
        $lastKey = (int) key($calcArray);

        for ($i = 0; $i < $lastKey; $i++) {
            if (!isset($calcArray[$i])) {
                $calcArray[$i] = 0;
            }
        }

        krsort($calcArray);

        // Covert calcArray into output we can understand
        foreach($calcArray as $days => $plays) {
            $set = [
                'days' => $days . ' ' . str_plural('day', $days),
                'plays' => $plays
            ];
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

        return $this->getTrackStatsHourly($id);
    }
}
