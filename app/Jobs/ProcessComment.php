<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2017 Peter Deltchev
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

namespace Poniverse\Ponyfm\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Notification;
use Poniverse\Ponyfm\Models\Comment;
use SerializesModels;

class ProcessComment extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $comment;

    /**
     * Create a new job instance.
     *
     * @param Comment $comment
     */
    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->beforeHandle();

        $replies = Comment::findMany($this->comment->getMentionedCommentIds());

        foreach ($replies as $reply) {
            Notification::newCommentReply($this->comment, $reply);
        }
    }
}
