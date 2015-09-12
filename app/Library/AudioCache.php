<?php

class AudioCache
{
    private static $_movieCache = array();

    /**
     * @param $filename
     * @return FFmpegMovie
     */
    public static function get($filename)
    {
        if (isset(self::$_movieCache[$filename])) {
            return self::$_movieCache[$filename];
        }

        return self::$_movieCache[$filename] = new FFmpegMovie($filename);
    }
}