<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Feld0.
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

namespace App\Jobs;

use Elasticsearch;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateSearchIndexForEntity extends Job implements ShouldQueue
{
    use InteractsWithQueue;

    protected array $elasticsearchBody;
    protected bool $removeFromIndex;

    /**
     * Create a new job instance.
     *
     * @param array $elasticsearchBody
     * @param bool $removeFromIndex
     */
    public function __construct(array $elasticsearchBody, bool $removeFromIndex = true)
    {
        $this->elasticsearchBody = $elasticsearchBody;
        $this->removeFromIndex = $removeFromIndex;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->beforeHandle();

        match($this->removeFromIndex) {
            true => $this->deleteElasticsearchEntry(),
            false => $this->createOrUpdateElasticsearchEntry(),
        };
    }

    private function createOrUpdateElasticsearchEntry()
    {
        Elasticsearch::connection()->index($this->elasticsearchBody);
    }

    private function deleteElasticsearchEntry()
    {
        try {
            Elasticsearch::connection()->delete($this->elasticsearchBody);
        } catch (Missing404Exception $e) {
            // If the entity we're trying to delete isn't indexed in Elasticsearch,
            // that's fine.
        }
    }

}
