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

namespace App\Contracts;

interface Searchable
{
    /**
     * Returns this model in Elasticsearch-friendly form. The array returned by
     * this method should match the current mapping for this model's ES type.
     *
     * @return array
     */
    public function toElasticsearch(): array;

    /**
     * @return bool whether this particular object should be indexed or not
     */
    public function shouldBeIndexed(): bool;

    public function updateElasticsearchEntry(bool $removeFromIndex = false);
}
