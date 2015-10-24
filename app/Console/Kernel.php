<?php

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
        \Poniverse\Ponyfm\Console\Commands\RebuildTags::class,
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
