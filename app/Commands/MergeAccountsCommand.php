<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Peter Deltchev
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

namespace Poniverse\Ponyfm\Commands;

use Carbon\Carbon;
use DB;
use Poniverse\Ponyfm\Models\Album;
use Poniverse\Ponyfm\Models\Comment;
use Poniverse\Ponyfm\Models\Favourite;
use Poniverse\Ponyfm\Models\Follower;
use Poniverse\Ponyfm\Models\Image;
use Poniverse\Ponyfm\Models\PinnedPlaylist;
use Poniverse\Ponyfm\Models\Playlist;
use Poniverse\Ponyfm\Models\ResourceLogItem;
use Poniverse\Ponyfm\Models\ResourceUser;
use Poniverse\Ponyfm\Models\Track;
use Poniverse\Ponyfm\Models\User;

class MergeAccountsCommand extends CommandBase
{
    private $sourceAccount;
    private $destinationAccount;

    public function __construct(User $sourceAccount, User $destinationAccount)
    {
        $this->sourceAccount = $sourceAccount;
        $this->destinationAccount = $destinationAccount;
    }

    /**
     * @throws \Exception
     * @return CommandResponse
     */
    public function execute()
    {
        DB::transaction(function () {
            $accountIds = [$this->sourceAccount->id];

            foreach (Album::whereIn('user_id', $accountIds)->get() as $album) {
                $album->user_id = $this->destinationAccount->id;
                $album->save();
            }

            foreach (Comment::whereIn('user_id', $accountIds)->get() as $comment) {
                $comment->user_id = $this->destinationAccount->id;
                $comment->save();
            }

            foreach (Favourite::whereIn('user_id', $accountIds)->get() as $favourite) {
                $favourite->user_id = $this->destinationAccount->id;
                $favourite->save();
            }

            foreach (Follower::whereIn('artist_id', $accountIds)->get() as $follow) {
                $follow->artist_id = $this->destinationAccount->id;
                $follow->save();
            }

            foreach (Image::whereIn('uploaded_by', $accountIds)->get() as $image) {
                $image->uploaded_by = $this->destinationAccount->id;
                $image->save();
            }

            foreach (Image::whereIn('uploaded_by', $accountIds)->get() as $image) {
                $image->uploaded_by = $this->destinationAccount->id;
                $image->save();
            }

            DB::table('oauth2_tokens')->whereIn('user_id', $accountIds)->update(['user_id' => $this->destinationAccount->id]);

            foreach (PinnedPlaylist::whereIn('user_id', $accountIds)->get() as $playlist) {
                $playlist->user_id = $this->destinationAccount->id;
                $playlist->save();
            }

            foreach (Playlist::whereIn('user_id', $accountIds)->get() as $playlist) {
                $playlist->user_id = $this->destinationAccount->id;
                $playlist->save();
            }

            foreach (ResourceLogItem::whereIn('user_id', $accountIds)->get() as $item) {
                $item->user_id = $this->destinationAccount->id;
                $item->save();
            }

            foreach (ResourceUser::whereIn('user_id', $accountIds)->get() as $item) {
                $item->user_id = $this->destinationAccount->id;
                $item->save();
            }

            foreach (Track::whereIn('user_id', $accountIds)->get() as $track) {
                $track->user_id = $this->destinationAccount->id;
                $track->save();
            }

            $this->sourceAccount->disabled_at = Carbon::now();
            $this->sourceAccount->save();
        });

        return CommandResponse::succeed();
    }
}
