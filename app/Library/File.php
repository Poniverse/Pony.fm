<?php

/**
 * File.
 *
 * Note: Remember to remove the "File" alias in APP_DIR/config/application.php
 *
 * @author Phill Sparks <me@phills.me.uk>
 */
class File extends \Illuminate\Support\Facades\File
{
    public static function inline($path, $mime, $name = null)
    {
        if (is_null($name)) {
            $name = basename($path);
        }

        $response = response(static::get($path));

        $response->header('Content-Type', $mime);
        $response->header('Content-Disposition', 'inline; filename="'.$name.'"');
        $response->header('Content-Transfer-Encoding', 'binary');
        $response->header('Expires', 0);
        $response->header('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
        $response->header('Pragma', 'public');
        $response->header('Content-Length', filesize($path));

        return $response;
    }
}
