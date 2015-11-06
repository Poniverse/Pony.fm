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

namespace Poniverse\Ponyfm\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \Poniverse\Ponyfm\Console\Commands\MigrateOldData::class,
        \Poniverse\Ponyfm\Console\Commands\RefreshCache::class,
        \Poniverse\Ponyfm\Console\Commands\ImportMLPMA::class,
        \Poniverse\Ponyfm\Console\Commands\ClassifyMLPMA::class,
        \Poniverse\Ponyfm\Console\Commands\PublishUnclassifiedMlpmaTracks::class,
        \Poniverse\Ponyfm\Console\Commands\RebuildTags::class,
        \Poniverse\Ponyfm\Console\Commands\RebuildArtists::class,
        \Poniverse\Ponyfm\Console\Commands\FixYearZeroLogs::class,
        \Poniverse\Ponyfm\Console\Commands\BootstrapLocalEnvironment::class,
        \Poniverse\Ponyfm\Console\Commands\PoniverseApiSetup::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
    }
}
