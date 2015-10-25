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

use Illuminate\Support\Facades\URL;

class Gravatar
{
    public static function getUrl($email, $size = 80, $default = null, $rating = 'g')
    {
        $url = 'https://www.gravatar.com/avatar/';
        $url .= md5(strtolower(trim($email)));
        $url .= "?s=$size&r=$rating";

        if ($default != null) {
            $url .= "&d=" . $default;
        } else {
            $size = 'normal';
            if ($size == 50) {
                $size = 'thumbnail';
            } else {
                if ($size == 100) {
                    $size = 'small';
                }
            }

            $url .= "&d=" . urlencode(URL::to('/images/icons/profile_' . $size . '.png'));
        }

        return $url;
    }
}
