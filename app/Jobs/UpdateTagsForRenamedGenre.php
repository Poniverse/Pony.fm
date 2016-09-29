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

namespace Poniverse\Ponyfm\Jobs;

use Auth;
use Cache;
use DB;
use Log;
use Poniverse\Ponyfm\Models\Genre;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Poniverse\Ponyfm\Models\Track;
use SerializesModels;

/**
 * Class RenameGenre
 *
 * NOTE: It is assumed that the genre passed into this job has already been renamed!
 * All this job does is update the tags in that genre's tracks.
 *
 * @package Poniverse\Ponyfm\Jobs
 */
class UpdateTagsForRenamedGenre extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $executingUser;
    protected $genreThatWasRenamed;
    protected $lockKey;

    /**
     * Create a new job instance.
     *
     * @param Genre $genreThatWasRenamed
     */
    public function __construct(Genre $genreThatWasRenamed)
    {
        $this->executingUser = Auth::user();
        $this->genreThatWasRenamed = $genreThatWasRenamed;

        $this->lockKey = "genre-{$this->genreThatWasRenamed->id}-tag-update-lock";
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
            Log::info("Tag updates for the \"{$this->genreThatWasRenamed->name}\" genre are currently in progress! Will try again in 30 seconds.");
            $this->release(30);
            return;
        } else {
            Cache::forever($this->lockKey, true);
        }


        $this->genreThatWasRenamed->tracks()->chunk(200, function ($tracks) {
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
