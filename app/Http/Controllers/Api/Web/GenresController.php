<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Feld0.
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

use App\Commands\CreateGenreCommand;
use App\Commands\DeleteGenreCommand;
use App\Commands\RenameGenreCommand;
use App\Http\Controllers\ApiControllerBase;
use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class GenresController extends ApiControllerBase
{
    public function getIndex()
    {
        $this->authorize('access-admin-area');

        $genres = Genre::with(['trackCountRelation' => function ($query) {
            $query->withTrashed();
        }])
            ->orderBy('name')
            ->get();

        return response()->json([
            'genres' => $genres->toArray(),
        ], 200);
    }

    public function postCreate(Request $request)
    {
        $command = new CreateGenreCommand($request->get('name'));

        return $this->execute($command);
    }

    public function putRename(Request $request, $genreId)
    {
        $command = new RenameGenreCommand($genreId, $request->get('name'));

        return $this->execute($command);
    }

    public function deleteGenre(Request $request, $genreId)
    {
        $command = new DeleteGenreCommand($genreId, $request->get('destination_genre_id'));

        return $this->execute($command);
    }
}
