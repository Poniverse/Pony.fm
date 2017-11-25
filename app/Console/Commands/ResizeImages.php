<?php

namespace Poniverse\Ponyfm\Console\Commands;

use Illuminate\Console\Command;

class ResizeImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rebuild:images';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resizes all images to fit the specifications in Models/Image';

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

    }
}
