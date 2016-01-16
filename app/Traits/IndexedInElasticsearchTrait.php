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

namespace Poniverse\Ponyfm\Traits;

use Elasticsearch;

/**
 * Class IndexedInElasticsearch
 *
 * Classes using this trait must declare the `$elasticsearchType` property
 * and use the `SoftDeletes` trait.
 *
 * @package Poniverse\Ponyfm\Traits
 */
trait IndexedInElasticsearchTrait
{
    /**
     * Returns this model in Elasticsearch-friendly form. The array returned by
     * this method should match the current mapping for this model's ES type.
     *
     * @return array
     */
    abstract public function toElasticsearch();

    public static function bootIndexedInElasticsearch() {
        static::saved(function ($model) {
            $model->createOrUpdateElasticsearchEntry();
        });

        static::deleted(function ($model) {
            $model->deleteElasticsearchEntry();
        });
    }

    /**
     * @param bool $includeBody set to false when deleting documents
     * @return array
     */
    private function getElasticsearchParameters(bool $includeBody = true) {
        $parameters = [
            'index' => 'ponyfm',
            'type'  => $this->elasticsearchType,
            'id'    => $this->id,
        ];

        if ($includeBody) {
            $parameters['body'] = $this->toElasticsearch();
        }

        return $parameters;
    }

    private function createOrUpdateElasticsearchEntry() {
        Elasticsearch::connection()->index($this->getElasticsearchParameters());
    }

    private function deleteElasticsearchEntry() {
        try {
            Elasticsearch::connection()->delete($this->getElasticsearchParameters(false));

        } catch (\Elasticsearch\Common\Exceptions\Missing404Exception $e) {
            // If the track we're trying to delete isn't indexed in Elasticsearch,
            // that's fine.
        }
    }

    public function ensureElasticsearchEntryIsUpToDate() {
        if (method_exists($this, 'trashed') && $this->trashed()) {
            $this->deleteElasticsearchEntry();
        } else {
            $this->createOrUpdateElasticsearchEntry();
        }
    }
}
