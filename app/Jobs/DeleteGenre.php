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

namespace Poniverse\Ponyfm\Jobs;

use Auth;
use DB;
use Poniverse\Ponyfm\Models\Genre;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Poniverse\Ponyfm\Models\Track;
use SerializesModels;

class DeleteGenre extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $executingUser;
    protected $genreToDelete;
    protected $destinationGenre;

    /**
     * Create a new job instance.
     *
     * @param Genre $genreToDelete
     * @param Genre $destinationGenre
     */
    public function __construct(Genre $genreToDelete, Genre $destinationGenre)
    {
        $this->executingUser = Auth::user();
        $this->genreToDelete = $genreToDelete;
        $this->destinationGenre = $destinationGenre;

        // The genre is deleted synchronously before the job is executed in
        // order to prevent race conditions.
        $this->genreToDelete->delete();
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

        // This is done instead of a single UPDATE query in order to
        // generate revision logs for the change.
        $this->genreToDelete->tracks()->chunk(200, function ($tracks) {
            foreach ($tracks as $track) {
                /** @var Track $track */

                $track->genre_id = $this->destinationGenre->id;
                $track->save();
                $track->updateTags();
            }
        });
    }
}
