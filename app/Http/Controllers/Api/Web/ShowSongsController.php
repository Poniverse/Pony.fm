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

use Illuminate\Http\Request;
use App\Commands\CreateShowSongCommand;
use App\Commands\DeleteShowSongCommand;
use App\Commands\RenameShowSongCommand;
use App\Http\Controllers\ApiControllerBase;
use App\Models\ShowSong;
use Illuminate\Support\Facades\Response;

class ShowSongsController extends ApiControllerBase
{
    public function getIndex()
    {
        $this->authorize('access-admin-area');

        $songs = ShowSong::with(['trackCountRelation' => function ($query) {
            $query->withTrashed();
        }])
            ->orderBy('title')
            ->select('id', 'title', 'slug')
            ->get();

        return response()->json([
            'showsongs' => $songs->toArray(),
        ], 200);
    }

    public function postCreate(Request $request)
    {
        $command = new CreateShowSongCommand($request->get('title'));

        return $this->execute($command);
    }

    public function putRename(Request $request, $songId)
    {
        $command = new RenameShowSongCommand($songId, $request->get('title'));

        return $this->execute($command);
    }

    public function deleteSong(Request $request, $songId)
    {
        $command = new DeleteShowSongCommand($songId, $request->get('destination_song_id'));

        return $this->execute($command);
    }
}
