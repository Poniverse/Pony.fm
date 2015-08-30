<?php

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

        $units = array('B', 'KB', 'MB', 'GB', 'TB');

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
        $content = date('F d, o \@ g:i:s a', strtotime($timestamp));

        return '<abbr class="timeago" title="' . $title . '">' . $content . '</abbr>';
    }
}