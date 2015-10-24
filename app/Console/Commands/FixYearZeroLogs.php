<?php

namespace Poniverse\Ponyfm\Console\Commands;

use Carbon\Carbon;
use Poniverse\Ponyfm\ResourceLogItem;
use Illuminate\Console\Command;

class FixYearZeroLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'poni:year-zero';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fills in missing timestamps in the resource_log_items table.';

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
        $items = ResourceLogItem::where('created_at', '0000-00-00 00:00:00')->orderBy('id', 'asc')->get();
        $totalItems = $items->count();

        // calculate the start and end of the logging gap
        $lastGoodId = (int) $items[0]->id - 1;
        $lastGoodItem = ResourceLogItem::find($lastGoodId);

        $lastGoodDate = $lastGoodItem->created_at;
        $now = Carbon::now();

        $secondsDifference = $now->diffInSeconds($lastGoodDate);
        $oneInterval = $secondsDifference / $totalItems;

        $this->info('Correcting records...');
        $bar = $this->output->createProgressBar($totalItems);

        foreach ($items as $i => $item) {
            $bar->advance();
            $dateOffset = (int) ($oneInterval * $i);
            $item->created_at = $lastGoodDate->copy()->addSeconds($dateOffset);
            $item->save();
        }

        $bar->finish();
        $this->line('');
        $this->info('All done!');
    }
}
