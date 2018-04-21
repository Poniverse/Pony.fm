<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Feld0
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

use Illuminate\Console\Command;
use Poniverse\Ponyfm\Models\User;

class RebuildArtists extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rebuild:artists';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recounts every user\'s tracks, ensuring that the artist directory isn\'t missing anyone.';

    /**
     * Create a new command instance.
     *
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
        $numberOfUsers = User::count();

        $bar = $this->output->createProgressBar($numberOfUsers);

        foreach (User::with(['tracks' => function ($query) {
            $query->published()->listed();
        }])->get() as $user) {
            $bar->advance();
            $user->track_count = $user->tracks->count();
            $user->save();
        }

        $bar->finish();
        $this->line('');
    }
}
