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
use Poniverse\Ponyfm\Track;
use Poniverse\Ponyfm\TrackType;

class PublishUnclassifiedMlpmaTracks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mlpma:declassify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This publishes all unpublished MLPMA tracks as the "unclassified" track type.';

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
        $affectedTracks = Track::mlpma()->
            whereNull('published_at')
            ->update([
                'track_type_id' => TrackType::UNCLASSIFIED_TRACK,
                'published_at'  => DB::raw('released_at'),
                'updated_at'  => Carbon::now(),
            ]);

        $this->info("Updated ${affectedTracks} tracks.");
    }
}
