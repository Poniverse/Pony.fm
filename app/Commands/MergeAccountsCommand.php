<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Feld0.
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

use App\Models\Album;
use App\Models\Comment;
use App\Models\EmailSubscription;
use App\Models\Favourite;
use App\Models\Follower;
use App\Models\Image;
use App\Models\Notification;
use App\Models\PinnedPlaylist;
use App\Models\Playlist;
use App\Models\ResourceLogItem;
use App\Models\ResourceUser;
use App\Models\Track;
use App\Models\User;
use Carbon\Carbon;
use DB;

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
     * Note: OAuth tokens are intentionally left untouched by the merge process.
     * The Artisan script performs some sanity checks to alert the admin to the
     * consequences of this.
     *
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

            /** @var Track $track */
            foreach (Track::whereIn('user_id', $accountIds)->get() as $track) {
                $track->user_id = $this->destinationAccount->id;
                $track->save();
            }

            /** @var EmailSubscription $emailSubscription */
            foreach ($this->sourceAccount->emailSubscriptions()->withTrashed()->get() as $emailSubscription) {
                // This keeps emails from being sent to disabled accounts.
                $emailSubscription->delete();
            }

            /** @var Notification $notification */
            foreach ($this->sourceAccount->notifications()->get() as $notification) {
                $notification->user_id = $this->destinationAccount->id;
                $notification->save();
            }

            $this->sourceAccount->disabled_at = Carbon::now();
            $this->sourceAccount->redirect_to = $this->destinationAccount->id;
            $this->sourceAccount->save();
        });

        return CommandResponse::succeed();
    }
}
