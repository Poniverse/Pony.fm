<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Peter Deltchev
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

namespace Poniverse\Ponyfm\Jobs;

use DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\InteractsWithQueue;
use Poniverse\Ponyfm\Contracts\Searchable;
use Poniverse\Ponyfm\Jobs\Job;
use Illuminate\Contracts\Bus\SelfHandling;
use SerializesModels;

class UpdateSearchIndexForEntity extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $entity;

    /**
     * Create a new job instance.
     *
     * @param Model $entity
     */
    public function __construct(Searchable $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::reconnect();
        $this->entity->updateElasticsearchEntrySynchronously();
    }
}
