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

use Poniverse\Ponyfm\Commands\UploadTrackCommand;
use Poniverse\Ponyfm\Http\Controllers\ApiControllerBase;
use Poniverse\Ponyfm\Models\Image;
use Poniverse\Ponyfm\Models\Track;
use Response;

class TracksController extends ApiControllerBase
{
    public function postUploadTrack() {
        session_write_close();

        $response = $this->execute(new UploadTrackCommand(true, true, session('api_client_id'), true));
        $commandData = $response->getData(true);

        if (200 !== $response->getStatusCode()) {
            return $response;
        }

        $data = [
            'id'            => (string) $commandData['id'],
            'status_url'    => action('Api\V1\TracksController@getUploadStatus', ['id' => $commandData['id']]),
            'track_url'     => action('TracksController@getTrack', ['id' => $commandData['id'], 'slug' => $commandData['slug']]),
            'message'       => $commandData['autoPublish']
                ? "This track has been accepted for processing! Poll the status_url to know when it has been published. It will be published at the track_url."
                : "This track has been accepted for processing! Poll the status_url to know when it's ready to publish. It will be published at the track_url.",
        ];

        $response->setData($data);
        $response->setStatusCode(202);
        return $response;
    }


    public function getUploadStatus($trackId) {
        $track = Track::findOrFail($trackId);
        $this->authorize('edit', $track);

        if ($track->status === Track::STATUS_PROCESSING) {
            return Response::json(['message' => 'Processing...'], 202);

        } elseif ($track->status === Track::STATUS_COMPLETE) {
            return Response::json([
                'message' => $track->published_at
                    ? 'Processing complete! The track is live at the track_url. The artist can edit the track by visiting its edit_url.'
                    : 'Processing complete! The artist must publish the track by visiting its edit_url.',
                'edit_url' => action('ContentController@getTracks', ['id' => $trackId]),
                'track_url' => $track->url
            ], 201);

        } else {
            // something went wrong
            return Response::json(['error' => 'Processing failed! Please contact logic@pony.fm to figure out what went wrong.'], 500);
        }
    }


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
