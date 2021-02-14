<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Feld0
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

namespace App\Commands;

use Notification;
use App\Models\Album;
use App\Models\Comment;
use App\Models\Playlist;
use App\Models\Track;
use App\Models\User;
use Auth;
use Validator;

class CreateCommentCommand extends CommandBase
{
    private $_input;
    private $_id;
    private $_type;

    public function __construct($type, $id, $input)
    {
        $this->_input = $input;
        $this->_id = $id;
        $this->_type = $type;
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        $user = \Auth::user();

        return $user != null;
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        $rules = [
            'content' => 'required',
            'track_id' => 'exists:tracks,id',
            'albums_id' => 'exists:albums,id',
            'playlist_id' => 'exists:playlists,id',
            'profile_id' => 'exists:users,id',
        ];

        $validator = Validator::make($this->_input, $rules);

        if ($validator->fails()) {
            return CommandResponse::fail($validator);
        }

        $comment = new Comment();
        $comment->user_id = Auth::user()->id;
        $comment->content = $this->_input['content'];

        if ($this->_type == 'track') {
            $column = 'track_id';
        } else {
            if ($this->_type == 'user') {
                $column = 'profile_id';
            } else {
                if ($this->_type == 'album') {
                    $column = 'album_id';
                } else {
                    if ($this->_type == 'playlist') {
                        $column = 'playlist_id';
                    } else {
                        App::abort(500);
                    }
                }
            }
        }

        $comment->$column = $this->_id;
        $comment->save();

        // Recount the track's comments, if this is a track comment
        if ($this->_type === 'track') {
            $entity = Track::find($this->_id);
        } elseif ($this->_type === 'album') {
            $entity = Album::find($this->_id);
        } elseif ($this->_type === 'playlist') {
            $entity = Playlist::find($this->_id);
        } elseif ($this->_type === 'user') {
            $entity = User::find($this->_id);
        } else {
            App::abort(400, 'This comment is being added to an invalid entity!');
        }

        $entity->comment_count = Comment::where($column, $this->_id)->count();
        $entity->save();
        
        Notification::newComment($comment);

        return CommandResponse::succeed(Comment::mapPublic($comment));
    }
}
