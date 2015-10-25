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

use Assetic\Asset\BaseAsset;
use Assetic\Filter\FilterInterface;

/**
 * Class CacheBusterAsset
 * OH GOD IT BUUUUUUURNS
 *
 * Well, I may as well tell you why this awful class exists. So... Assetic doesn't quite support less's import
 * directive. I mean; it supports it insofar as Less itself supports it - but it doesn't take into account the
 * last modified time for imported assets. Since we only have one less file that imports everything else... well
 * you can see where this is going. This asset will let us override the last modified time for an entire collection
 * which allows me to write a custom mechanism for cache busting.
 */
class CacheBusterAsset extends BaseAsset
{
    private $_lastModified;

    /**
     * @param int $lastModified
     */
    public function __construct($lastModified)
    {
        $this->_lastModified = $lastModified;
        parent::__construct([], '', '', []);
    }

    public function load(FilterInterface $additionalFilter = null)
    {
    }

    public function getLastModified()
    {
        return $this->_lastModified;
    }
}
