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

namespace Poniverse\Ponyfm\Http\Controllers\Api\Web;

use Illuminate\Support\Facades\Request;
use Poniverse\Ponyfm\Commands\CreateShowSongCommand;
use Poniverse\Ponyfm\Commands\DeleteShowSongCommand;
use Poniverse\Ponyfm\Commands\RenameShowSongCommand;
use Poniverse\Ponyfm\Http\Controllers\ApiControllerBase;
use Poniverse\Ponyfm\Models\ShowSong;
use Response;

class ShowSongsController extends ApiControllerBase
{
    public function getIndex()
    {
        $this->authorize('access-admin-area');

        $songs = ShowSong::with(['trackCountRelation' => function ($query) {
            $query->withTrashed();
        }])
            ->orderBy('title', 'asc')
            ->select('id', 'title', 'slug')
            ->get();

        return Response::json([
            'showsongs' => $songs->toArray(),
        ], 200);
    }

    public function postCreate()
    {
        $command = new CreateShowSongCommand(Request::get('title'));

        return $this->execute($command);
    }

    public function putRename($songId)
    {
        $command = new RenameShowSongCommand($songId, Request::get('title'));

        return $this->execute($command);
    }

    public function deleteSong($songId)
    {
        $command = new DeleteShowSongCommand($songId, Request::get('destination_song_id'));

        return $this->execute($command);
    }
}
