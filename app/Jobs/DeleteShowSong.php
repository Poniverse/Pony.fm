<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Josef Citrine
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
use Poniverse\Ponyfm\Models\ShowSong;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Poniverse\Ponyfm\Models\Track;
use SerializesModels;

class DeleteShowSong extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $executingUser;
    protected $songToDelete;
    protected $destinationSong;

    /**
     * Create a new job instance.
     *
     * @param ShowSong $songToDelete
     * @param ShowSong $destinationSong
     */
    public function __construct(ShowSong $songToDelete, ShowSong $destinationSong)
    {
        $this->executingUser = Auth::user();
        $this->songToDelete = $songToDelete;
        $this->destinationSong = $destinationSong;
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
        $this->songToDelete->tracks()->chunk(200, function($tracks) {
            foreach ($tracks as $track) {
                /** @var Track $track */
                $oldSongs = $track->showSongs;
                $newSongs = [];

                foreach ($oldSongs as $key => $showSong) {
                    if ($showSong->id == $this->songToDelete->id) {
                        $newSongs[$key] = $this->destinationSong->id;
                    } else {
                        $newSongs[$key] = $showSong->id;
                    }
                }

                $track->showSongs()->sync($newSongs);
                $track->save();
                $track->updateTags();
            }
        });

        $this->songToDelete->delete();
    }
}
