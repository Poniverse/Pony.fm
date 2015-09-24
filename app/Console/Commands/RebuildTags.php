<?php

namespace App\Console\Commands;

use App\Track;
use Illuminate\Console\Command;

class RebuildTags extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rebuild:tags
                            {trackId? : ID of the track to rebuild tags for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rewrites tags in track files, ensuring they\'re up to date.';

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
        if ($this->argument('trackId')) {
            $track = Track::findOrFail($this->argument('trackId'));
            $tracks = [$track];

        } else {
            $tracks = Track::whereNotNull('published_at')->orderBy('id', 'asc')->get();
        }

        $bar = $this->output->createProgressBar(sizeof($tracks));

        foreach($tracks as $track) {
            $this->comment('Rewriting tags for track #'.$track->id.'...');
            $track->updateTags();
            $bar->advance();
            $this->line('');
        }

        $bar->finish();
        $this->line('');
    }
}
