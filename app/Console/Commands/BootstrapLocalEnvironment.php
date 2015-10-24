<?php

namespace Poniverse\Ponyfm\Console\Commands;

use Illuminate\Console\Command;

class BootstrapLocalEnvironment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'poni:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs post-provisioning steps to set up the dev environment\'s config.';

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
        $this->call('key:generate');
        $this->call('poni:api-setup');
    }
}
