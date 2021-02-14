<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Logic.
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

namespace App\Jobs;

use App\Models\ShowSong;
use App\Models\Track;
use Auth;
use Cache;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Log;
use SerializesModels;

/**
 * Class RenameGenre.
 *
 * NOTE: It is assumed that the genre passed into this job has already been renamed!
 * All this job does is update the tags in that genre's tracks.
 */
class UpdateTagsForRenamedShowSong extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $executingUser;
    protected $songThatWasRenamed;
    protected $lockKey;

    /**
     * Create a new job instance.
     *
     * @param ShowSong $songThatWasRenamed
     */
    public function __construct(ShowSong $songThatWasRenamed)
    {
        $this->executingUser = Auth::user();
        $this->songThatWasRenamed = $songThatWasRenamed;

        $this->lockKey = "show-song-{$this->songThatWasRenamed->id}-tag-update-lock";
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->beforeHandle();

        // The user who kicked off this job is used when generating revision log entries.
        Auth::login($this->executingUser);

        // "Lock" this genre to prevent race conditions
        if (Cache::has($this->lockKey)) {
            Log::info("Tag updates for the \"{$this->songThatWasRenamed->title}\" song are currently in progress! Will try again in 30 seconds.");
            $this->release(30);

            return;
        } else {
            Cache::forever($this->lockKey, true);
        }

        $this->songThatWasRenamed->tracks()->chunk(200, function ($tracks) {
            foreach ($tracks as $track) {
                /** @var Track $track */
                $track->updateTags();
            }
        });

        Cache::forget($this->lockKey);
    }

    public function failed()
    {
        Cache::forget($this->lockKey);
    }
}
