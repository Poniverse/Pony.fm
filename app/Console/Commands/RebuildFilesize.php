<?php

namespace Poniverse\Ponyfm\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Poniverse\Ponyfm\TrackFile;

class RebuildFilesize extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'filesize:rebuild
                            {--force : Skip all prompts.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuilds the filesize cache for each track file which currently exists on disk.';

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
        $this->info('This will only rebuild the cache for track files which exist on disk; non-existent files will be skipped.');

        if ($this->option('force') || $this->confirm('Are you sure you want to rebuild the filesize cache? [y|N]',
                false)
        ) {

            TrackFile::chunk(200, function ($trackFiles) {

                $this->info('========== Start Chunk ==========');

                foreach ($trackFiles as $trackFile) {
                    $file = $trackFile->getFile();

                    if (File::exists($file)) {
                        $size = File::size($file);
                        $this->info('ID ' . $trackFile->id . ' processed - ' . $size . ' bytes');
                    } else {
                        $size = null;
                        $this->info('ID ' . $trackFile->id . ' skipped');
                    }

                    $trackFile->filesize = $size;
                    $trackFile->update();
                }

                $this->info('=========== End Chunk ===========');

            });

            $this->info('Rebuild complete. Exiting.');

        } else {
            $this->info('Rebuild cancelled. Exiting.');
        }
    }
}
