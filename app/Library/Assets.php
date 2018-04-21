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

class Assets
{
    public static function scriptIncludes(string $area)
    {
        $scriptTags = '';

        if ('app' === $area) {
            $scripts = ['app.js', 'templates.js'];
        } elseif ('embed' === $area) {
            $scripts = ['embed.js'];
        } else {
            throw new InvalidArgumentException('A valid app area must be specified!');
        }

        foreach ($scripts as $filename) {
            if (Config::get('app.debug') && $filename !== 'templates.js') {
                $scriptTags .= "<script src='http://localhost:61999/build/scripts/{$filename}'></script>";
            } else {
                $scriptTags .= "<script src='/build/scripts/{$filename}?" . filemtime(public_path("build/scripts/{$filename}")) . "'></script>";
            }
        }

        if (Config::get('app.debug')) {
            $scriptTags .= '<script src="http://localhost:61999/webpack-dev-server.js"></script>';
        }

        return $scriptTags;
    }

    public static function styleIncludes($area = 'app')
    {
        if (!Config::get("app.debug")) {
            return '<script>document.write(\'<link rel="stylesheet" href="build/styles/' . $area . '.css?' .
                   filemtime(public_path("/build/styles/${area}.css"))
                   . '" />\');</script>';
        }

        $styles = self::mergeGlobs(self::getStylesForArea($area));
        $retVal = "";

        foreach ($styles as $style) {
            $filename = self::replaceExtensionWith($style, ".less", ".css");
            $retVal .= "<link rel='stylesheet' href='/build/$filename?" .filemtime(public_path("/build/${filename}")). "' />";
        }

        return $retVal;
    }

    private static function replaceExtensionWith($filename, $fromExtension, $toExtension)
    {
        $fromLength = strlen($fromExtension);

        return substr($filename, -$fromLength) == $fromExtension
            ? substr($filename, 0, strlen($filename) - $fromLength) . $toExtension
            : $filename;
    }

    /** Merges an array of paths that are passed into "glob" into a list of unique filenames.
     * Note that this method assumes the globs should be relative to the "app" folder of this project */
    private static function mergeGlobs($globs)
    {
        $files = [];
        $filesFound = [];
        foreach ($globs as $glob) {
            foreach (glob("../resources/assets/" . $glob, GLOB_BRACE) as $file) {
                if (isset($filesFound[$file])) {
                    continue;
                }

                $filesFound[$file] = true;
                $files[] = substr($file, 20); // chop off ../app/
            }
        }
        return $files;
    }

    private static function getStylesForArea($area)
    {
        if ($area == 'app') {
            return [
                "styles/base/jquery-ui.css",
                "styles/base/colorbox.css",
                "styles/app.less",
            ];
        } else {
            if ($area == 'embed') {
                return [
                    "styles/embed.less"
                ];
            }
        }

        throw new Exception();
    }
}
