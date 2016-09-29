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

use App;
use Poniverse\Ponyfm\Commands\CreateCommentCommand;
use Poniverse\Ponyfm\Models\Comment;
use Poniverse\Ponyfm\Http\Controllers\ApiControllerBase;
use Illuminate\Support\Facades\Request;
use Response;

class CommentsController extends ApiControllerBase
{
    public function postCreate($type, $id)
    {
        return $this->execute(new CreateCommentCommand($type, $id, Request::all()));
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
                        App::abort(500);
                    }
                }
            }
        }

        $query = Comment::where($column, '=', $id)->orderBy('created_at', 'desc')->with('user');
        $comments = [];

        foreach ($query->get() as $comment) {
            $comments[] = Comment::mapPublic($comment);
        }

        return Response::json(['list' => $comments, 'count' => count($comments)]);
    }
}
