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

use Illuminate\Contracts\Database\ModelIdentifier;

/**
 * Class SerializesModels
 * This version of the SerializesModel trait overrides a method to make it work
 * with soft-deletable models.
 *
 * @link https://github.com/laravel/framework/issues/9347#issuecomment-120803564
 */
trait SerializesModels
{
    use \Illuminate\Queue\SerializesModels;

    /**
     * Get the restored property value after deserialization.
     *
     * @param  mixed $value
     * @return mixed
     */
    protected function getRestoredPropertyValue($value)
    {
        if ($value instanceof ModelIdentifier) {
            return method_exists($value->class, 'withTrashed')
                ? (new $value->class)->withTrashed()->findOrFail($value->id)
                : (new $value->class)->findOrFail($value->id);
        } else {
            return $value;
        }
    }
}
