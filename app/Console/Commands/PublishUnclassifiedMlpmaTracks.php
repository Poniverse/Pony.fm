<?php

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
