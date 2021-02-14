<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Feld0
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

namespace App\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Class TrackFileNotFoundException
 *
 * This exception is used to indicate that the requested `TrackFile` object
 * does not exist. This is useful when dealing with albums or playlists that
 * contain tracks for which no lossless master is available (and thus, lossless
 * `TrackFiles` don't exist for).
 */
class TrackFileNotFoundException extends ModelNotFoundException
{
}
