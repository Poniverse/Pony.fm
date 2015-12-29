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

namespace Poniverse\Ponyfm\Console\Commands;

use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Poniverse\Ponyfm\Album;
use Poniverse\Ponyfm\Comment;
use Poniverse\Ponyfm\Favourite;
use Poniverse\Ponyfm\Follower;
use Poniverse\Ponyfm\Image;
use Poniverse\Ponyfm\PinnedPlaylist;
use Poniverse\Ponyfm\Playlist;
use Poniverse\Ponyfm\ResourceLogItem;
use Poniverse\Ponyfm\ResourceUser;
use Poniverse\Ponyfm\Track;
use Poniverse\Ponyfm\User;

class MergeDuplicateAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:merge-duplicates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Merges duplicate accounts';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Get list of affected users
        $usernames = DB::table('users')
            ->select(['username', DB::raw('COUNT(*) as count')])
            ->whereNull('disabled_at')
            ->groupBy(DB::raw('LOWER(username)'))
            ->having('count', '>=', 2)
            ->lists('username');

        foreach($usernames as $username) {
            // Find the relevant accounts
            // ==========================

            /** @var Collection $accounts */
            $accounts = User::where('username', $username)->orderBy('created_at', 'ASC')->get();
            $firstAccount = $accounts[0];
            $accounts->forget(0);
            $accountIds = $accounts->pluck('id');


            // Reassign content
            // ================
            // This is done with the less-efficient-than-raw-SQL Eloquent
            // methods to generate appropriate revision logs.

            $this->info('Merging duplicates for: '.$firstAccount->username);
            DB::transaction(function() use ($accounts, $accountIds, $firstAccount) {
                foreach (Album::whereIn('user_id', $accountIds)->get() as $album) {
                    $album->user_id = $firstAccount->id;
                    $album->save();
                }

                foreach (Comment::whereIn('user_id', $accountIds)->get() as $comment) {
                    $comment->user_id = $firstAccount->id;
                    $comment->save();
                }

                foreach (Favourite::whereIn('user_id', $accountIds)->get() as $favourite) {
                    $favourite->user_id = $firstAccount->id;
                    $favourite->save();
                }

                foreach (Follower::whereIn('artist_id', $accountIds)->get() as $follow) {
                    $follow->artist_id = $firstAccount->id;
                    $follow->save();
                }

                foreach (Image::whereIn('uploaded_by', $accountIds)->get() as $image) {
                    $image->uploaded_by = $firstAccount->id;
                    $image->save();
                }

                foreach (Image::whereIn('uploaded_by', $accountIds)->get() as $image) {
                    $image->uploaded_by = $firstAccount->id;
                    $image->save();
                }

                DB::table('oauth2_tokens')->whereIn('user_id', $accountIds)->update(['user_id' => $firstAccount->id]);

                foreach (PinnedPlaylist::whereIn('user_id', $accountIds)->get() as $playlist) {
                    $playlist->user_id = $firstAccount->id;
                    $playlist->save();
                }

                foreach (Playlist::whereIn('user_id', $accountIds)->get() as $playlist) {
                    $playlist->user_id = $firstAccount->id;
                    $playlist->save();
                }

                foreach (ResourceLogItem::whereIn('user_id', $accountIds)->get() as $item) {
                    $item->user_id = $firstAccount->id;
                    $item->save();
                }

                foreach (ResourceUser::whereIn('user_id', $accountIds)->get() as $item) {
                    $item->user_id = $firstAccount->id;
                    $item->save();
                }

                foreach (Track::whereIn('user_id', $accountIds)->get() as $track) {
                    $track->user_id = $firstAccount->id;
                    $track->save();
                }

                foreach($accounts as $account) {
                    $account->disabled_at = Carbon::now();
                    $account->save();
                }
            });
        }
    }
}
