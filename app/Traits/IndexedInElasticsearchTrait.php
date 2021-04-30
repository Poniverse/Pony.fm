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

namespace App\Traits;

use App\Contracts\Searchable;
use App\Jobs\UpdateSearchIndexForEntity;
use Elasticsearch;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Facades\Config;

/**
 * Class IndexedInElasticsearch.
 *
 * Classes using this trait must declare the `$elasticsearchType` property and
 * implement the `Searchable` interface.
 */
trait IndexedInElasticsearchTrait
{
    use DispatchesJobs;

    // These two functions are from the Searchable interface. They're included
    // here, without being implemented, to assist IDE's when editing this trait.
    abstract public function toElasticsearch():array;

    abstract public function shouldBeIndexed():bool;

    // Laravel automatically runs this method based on the trait's name. #magic
    public static function bootIndexedInElasticsearchTrait()
    {
        static::saved(function (Searchable $entity) {
            $entity->updateElasticsearchEntry();
        });

        static::deleted(function (Searchable $entity) {
            $entity->updateElasticsearchEntry(true);
        });
    }

    /**
     * @param bool $includeBody set to false when deleting documents
     * @return array
     */
    private function getElasticsearchParameters(bool $includeBody = true)
    {
        $parameters = [
            'index' => config('ponyfm.elasticsearch_index')."-".$this->elasticsearchType,
            'type'  => $this->elasticsearchType,
            'id'    => $this->id,
        ];

        if ($includeBody) {
            $parameters['body'] = $this->toElasticsearch();
        }

        return $parameters;
    }

    /**
     * Asynchronously updates the Elasticsearch entry.
     * When in doubt, this is the method to use.
     *
     * @param bool $removeFromIndex
     */
    public function updateElasticsearchEntry(bool $removeFromIndex = false)
    {
        // If it shouldn't be indexed, always force a removal from the index.
        if (!$this->shouldBeIndexed()) {
            $removeFromIndex = true;
        }

        $job = (new UpdateSearchIndexForEntity($this->getElasticsearchParameters(!$removeFromIndex), $removeFromIndex))->onQueue(config('ponyfm.indexing_queue'));
        $this->dispatch($job);
    }
}
