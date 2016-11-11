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

use Carbon\Carbon;
use Poniverse\Ponyfm\Http\Controllers\Controller;
use Poniverse\Ponyfm\Models\Announcement;
use Response;

class AnnouncementsController extends Controller {
    public function getIndex() {
        $currentDate = Carbon::now();

        $query = Announcement::whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->where('start_time', '<', $currentDate)
            ->where('end_time', '>', $currentDate)
            ->orderBy('start_time', 'desc');

        $announcement = $query->first();

        return Response::json(
            ["announcement" => $announcement],
            200
        );
    }
}
