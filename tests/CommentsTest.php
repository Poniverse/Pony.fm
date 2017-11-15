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

use Carbon\Carbon;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Poniverse\Ponyfm\Models\Album;
use Poniverse\Ponyfm\Models\Comment;
use Poniverse\Ponyfm\Models\Genre;
use Poniverse\Ponyfm\Models\Track;
use Poniverse\Ponyfm\Models\User;

class CommentsTest extends TestCase {
    use DatabaseMigrations;
    use WithoutMiddleware;

    public function testCommentMentionsParsing() {
        /** @var Comment $comment */
        $comment = factory(Comment::class)->make();

        $comment->content = <<<EOF
>c1234 This>c24 is an awesome track!!! >c65437
>u4678
>4bsfsd
gfdsgfds>c16437boomboom
>47
>c44
EOF;
        $this->assertEquals([1234, 65437, 44], $comment->getMentionedCommentIds());
    }
}
