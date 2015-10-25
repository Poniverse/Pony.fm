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
