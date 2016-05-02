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

namespace Poniverse\Ponyfm\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * This interface is used for type safety when referring to entities that
 * are capable of being favourited.
 *
 * @package Poniverse\Ponyfm\Contracts
 */
interface Favouritable {
    /**
     * This method returns an Eloquent relation to the entity's favourites.
     *
     * @return HasMany
     */
    public function favourites():HasMany;
}
