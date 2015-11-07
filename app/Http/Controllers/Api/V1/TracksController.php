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

namespace Poniverse\Ponyfm\Http\Controllers\Api\V1;

use Poniverse\Ponyfm\Image;
use Poniverse\Ponyfm\Track;
use Cover;
use Illuminate\Support\Facades\Response;

class TracksController extends \ApiControllerBase
{
    public function getTrackRadioDetails($hash)
    {
        $track = Track
            ::with('user', 'album', 'user.avatar', 'cover', 'comments', 'genre')
            ->published()
            ->whereHash($hash)->first();

        if (!$track) {
            return Response::json(['message' => 'Track not found.'], 403);
        }

        $comments = [];
        foreach ($track->comments as $comment) {
            $comments[] = [
                'id' => $comment->id,
                'created_at' => $comment->created_at,
                'content' => $comment->content,
                'user' => [
                    'name' => $comment->user->display_name,
                    'id' => $comment->user->id,
                    'url' => $comment->user->url,
                    'avatars' => [
                        'normal' => $comment->user->getAvatarUrl(Image::NORMAL),
                        'thumbnail' => $comment->user->getAvatarUrl(Image::THUMBNAIL),
                        'small' => $comment->user->getAvatarUrl(Image::SMALL),
                    ]
                ]
            ];
        }

        return Response::json([
            'id' => $track->id,
            'title' => $track->title,
            'description' => $track->description,
            'lyrics' => $track->lyrics,
            'user' => [
                'id' => $track->user->id,
                'name' => $track->user->display_name,
                'url' => $track->user->url,
                'avatars' => [
                    'thumbnail' => $track->user->getAvatarUrl(Image::THUMBNAIL),
                    'small' => $track->user->getAvatarUrl(Image::SMALL),
                    'normal' => $track->user->getAvatarUrl(Image::NORMAL)
                ]
            ],
            'stats' => [
                'views' => $track->view_count,
                'plays' => $track->play_count,
                'downloads' => $track->download_count,
                'comments' => $track->comment_count,
                'favourites' => $track->favourite_count
            ],
            'url' => $track->url,
            'is_vocal' => !!$track->is_vocal,
            'is_explicit' => !!$track->is_explicit,
            'is_downloadable' => !!$track->is_downloadable,
            'published_at' => $track->published_at,
            'duration' => $track->duration,
            'genre' => $track->genre != null
                ?
                [
                    'id' => $track->genre->id,
                    'name' => $track->genre->name
                ] : null,
            'type' => [
                'id' => $track->track_type->id,
                'name' => $track->track_type->title
            ],
            'covers' => [
                'thumbnail' => $track->getCoverUrl(Image::THUMBNAIL),
                'small' => $track->getCoverUrl(Image::SMALL),
                'normal' => $track->getCoverUrl(Image::NORMAL)
            ],
            'comments' => $comments,

            // As of 2015-10-28, this should be expected to produce either
            // "direct_upload" or "mlpma" for all tracks.
            'source' => $track->source
        ], 200);
    }
}
