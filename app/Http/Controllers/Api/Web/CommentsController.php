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

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Commands\CreateCommentCommand;
use App\Http\Controllers\ApiControllerBase;
use App\Models\Comment;
use Illuminate\Support\Facades\Response;

class CommentsController extends ApiControllerBase
{
    public function postCreate(Request $request, $type, $id)
    {
        return $this->execute(new CreateCommentCommand($type, $id, $request->all()));
    }

    public function getIndex($type, $id)
    {
        $column = '';

        if ($type == 'track') {
            $column = 'track_id';
        } else {
            if ($type == 'user') {
                $column = 'profile_id';
            } else {
                if ($type == 'album') {
                    $column = 'album_id';
                } else {
                    if ($type == 'playlist') {
                        $column = 'playlist_id';
                    } else {
                        abort(500);
                    }
                }
            }
        }

        $query = Comment::where($column, '=', $id)->orderByDesc('created_at')->with('user');
        $comments = [];

        foreach ($query->get() as $comment) {
            $comments[] = Comment::mapPublic($comment);
        }

        return response()->json(['list' => $comments, 'count' => count($comments)]);
    }
}
