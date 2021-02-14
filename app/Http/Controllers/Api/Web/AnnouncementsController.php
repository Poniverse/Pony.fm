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

namespace App\Http\Controllers\Api\Web;

use App\Commands\CreateAnnouncementCommand;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Carbon\Carbon;
use Request;
use Response;

class AnnouncementsController extends Controller
{
    public function getIndex()
    {
        $currentDate = Carbon::now();

        $query = Announcement::whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->where('start_time', '<', $currentDate)
            ->where('end_time', '>', $currentDate)
            ->orderBy('start_time', 'desc');

        $announcement = $query->first();

        return Response::json(
            ['announcement' => $announcement],
            200
        );
    }

    public function getAdminIndex()
    {
        $this->authorize('access-admin-area');

        $announcements = Announcement::orderBy('start_time', 'desc')
            ->get();

        return Response::json([
            'announcements' => $announcements->toArray(),
        ], 200);
    }

    public function getItemById($genreId)
    {
        $this->authorize('access-admin-area');

        $query = Announcement::where('id', '=', $genreId)
            ->orderBy('start_time', 'desc');

        $announcement = $query->first();

        return Response::json(
            ['announcement' => $announcement],
            200
        );
    }

    public function postCreate()
    {
        $command = new CreateAnnouncementCommand(Request::get('name'));

        return $this->execute($command);
    }
}
