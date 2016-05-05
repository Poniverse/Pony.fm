<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Josef Citrine
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
use Poniverse\Ponyfm\Models\ResourceLogItem;
use Poniverse\Ponyfm\Models\Track;
use Auth;
use Cache;
use DB;
use Response;
use Carbon\Carbon;

class StatsController extends ApiControllerBase
{
    private function getStatsData($id, $hourly = false) {
        $playRange = "1 MONTH";

        if ($hourly) {
            $playRange = "2 DAY";
        }

        $statQuery = DB::table('resource_log_items')
            ->selectRaw('created_at, COUNT(1) AS `plays`')
            ->where('track_id', '=', $id)
            ->where('log_type', '=', ResourceLogItem::PLAY)
            ->whereRaw('`created_at` > now() - INTERVAL ' . $playRange)
            ->groupBy('created_at')
            ->orderBy('created_at')
            ->get();

        return $statQuery;
    }

    private function sortTrackStatsArray($query, $hourly = false) {
        $now = Carbon::now();
        $playsArray = [];
        $output = [];

        if ($hourly) {
            $playsArray = array_fill(0, 24, 0);
        } else {
            $playsArray = array_fill(0, 30, 0);
        }

        foreach($query as $item) {
            $playDate = new Carbon($item->created_at);

            $key = 0;
            if ($hourly) {
                $key = $playDate->diffInHours($now);
            } else {
                $key = $playDate->diffInDays($now);
            }

            if (array_key_exists($key, $playsArray)) {
                $playsArray[$key] += $item->plays;
            } else {
                $playsArray[$key] = $item->plays;
            }
        }

        krsort($playsArray);

        // Covert playsArray into output we can understand
        foreach($playsArray as $timeOffet => $plays) {
            if ($hourly) {
                $set = [
                    'hours' => $timeOffet . ' ' . str_plural('hour', $timeOffet),
                    'plays' => $plays
                ];
            } else {
                $set = [
                    'days' => $timeOffet . ' ' . str_plural('day', $timeOffet),
                    'plays' => $plays
                ];
            }
            array_push($output, $set);
        }

        if ($hourly) {
            return Response::json(['playStats' => $output, 'type' => 'Hourly'], 200);
        } else {
            return Response::json(['playStats' => $output, 'type' => 'Daily'], 200);
        }
    }

    public function getTrackStats($id) {
        $cachedOutput = Cache::remember('track_stats'.$id, 5, function() use ($id) {
            try {
                $track = Track::published()->findOrFail($id);
            } catch (ModelNotFoundException $e) {
                return $this->notFound('Track not found!');
            }

            // Do we have permission to view this track?
            if (!$track->canView(Auth::user())) {
                return $this->notFound('Track not found!');
            }

            // Run one of the functions depending on
            // how old the track is
            $now = Carbon::now();
            $trackDate = $track->published_at;

            $hourly = true;

            if ($trackDate->diffInDays($now) >= 1) {
                $hourly = false;
            }

            $statsData = $this->getStatsData($id, $hourly);

            $output = $this->sortTrackStatsArray($statsData, $hourly);
            return $output;
        });

        return $cachedOutput;
    }
}
