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

class StatsController extends ApiControllerBase
{
    public function getTrackStatsHourly($id)
    {
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

        // I know, raw SQL ugh. I'm not used to laravel's query system
        $query = DB::select(DB::raw("
            SELECT HOUR(created_at) AS `hour`, COUNT(1) AS `plays`
            FROM `resource_log_items`
            WHERE `track_id` = :id AND `log_type` = 3 AND `created_at` > now() - INTERVAL 1 DAY
            GROUP BY HOUR(created_at);"), array(
            'id' => $id
        ));

        $currentHour = intval(date("H"));

        foreach($query as $item) {
            // Set hours to offsets of the current hour
            $item->hour = $item->hour - $currentHour;
        }

        return Response::json(['playsHourly' => $query], 200);
    }
}
