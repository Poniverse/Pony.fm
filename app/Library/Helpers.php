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

class Helpers
{
    /**
     * Removes whitespace and special characters from a string
     * and sets all characters to lower case.
     */
    public static function sanitizeInputForHashing($value)
    {
        $value = preg_replace('/[^A-Za-z0-9]/', '', $value);

        return strtolower($value);
    }

    public static function template($template)
    {
        echo file_get_contents('templates/' . $template);
    }

    public static function angular($expression)
    {
        return '{{' . $expression . '}}';
    }

    public static function formatBytes($bytes, $precision = 2)
    {
        if ($bytes == 0) {
            return '0 MB';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * timeago-style timestamp generator macro.
     *
     * @param string $timestamp A timestamp in SQL DATETIME syntax
     * @return string
     */
    public static function timestamp($timestamp)
    {
        if (gettype($timestamp) !== 'string' && get_class($timestamp) === 'DateTime') {
            $timestamp = $timestamp->format('c');
        }

        $title = date('c', strtotime($timestamp));
        $content = date('F j, Y \@ g:i:s a', strtotime($timestamp));

        return '<abbr class="timeago" title="' . $title . '">' . $content . '</abbr>';
    }

    /**
     * Converts an RGB array to a hex string
     *
     * @param array[int] $rgb RGB values in an array
     * @return string
     */
    public static function rgb2hex($rgb)
    {
        $hex = "#";
        $hex .= str_pad(dechex($rgb[0]), 2, "0", STR_PAD_LEFT);
        $hex .= str_pad(dechex($rgb[1]), 2, "0", STR_PAD_LEFT);
        $hex .= str_pad(dechex($rgb[2]), 2, "0", STR_PAD_LEFT);

        return $hex;
    }
}
