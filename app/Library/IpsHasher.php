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

use Illuminate\Contracts\Hashing\Hasher;

class IpsHasher implements Hasher
{
    public function make($value, array $options = array())
    {
        return md5(md5($options['salt']) . md5(static::ips_sanitize($value)));
    }

    public function check($value, $hashedValue, array $options = array())
    {
        return static::make($value, ['salt' => $options['salt']]) === $hashedValue;
    }

    public function needsRehash($hashedValue, array $options = array())
    {
        return false;
    }

    static public function ips_sanitize($value)
    {
        $value = str_replace('&', '&amp;', $value);
        $value = str_replace('\\', '&#092;', $value);
        $value = str_replace('!', '&#33;', $value);
        $value = str_replace('$', '&#036;', $value);
        $value = str_replace('"', '&quot;', $value);
        $value = str_replace('<', '&lt;', $value);
        $value = str_replace('>', '&gt;', $value);
        $value = str_replace('\'', '&#39;', $value);

        return $value;
    }
}
