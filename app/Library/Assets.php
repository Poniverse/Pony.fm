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

class Assets
{
    public static function scriptIncludes($area = 'app')
    {
        if (!Config::get("app.debug")) {
            return '<script src="/build/scripts/' . $area . '.js?' . filemtime(public_path("/build/scripts/${area}.js")) . '"></script>';
        }

        $scripts = self::mergeGlobs(self::getScriptsForArea($area));
        $retVal = "";

        foreach ($scripts as $script) {
            $filename = self::replaceExtensionWith($script, ".coffee", ".js");
            $retVal .= "<script src='/build/$filename?" . filemtime(public_path("/build/${filename}")) . "'></script>";
        }

        return $retVal;
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

    private static function getScriptsForArea($area)
    {
        if ($area == 'app') {
            return [
                "scripts/base/jquery-2.0.2.js",
                "scripts/base/angular.js",
                "scripts/base/marked.js",
                "scripts/base/*.{coffee,js}",
                "scripts/shared/*.{coffee,js}",
                "scripts/app/*.{coffee,js}",
                "scripts/app/services/*.{coffee,js}",
                "scripts/app/filters/*.{coffee,js}",
                "scripts/app/directives/*.{coffee,js}",
                "scripts/app/controllers/*.{coffee,js}",
                "scripts/**/*.{coffee,js}"
            ];
        } else {
            if ($area == 'embed') {
                return [
                    "scripts/base/jquery-2.0.2.js",
                    "scripts/base/jquery.cookie.js",
                    "scripts/base/jquery.viewport.js",
                    "scripts/base/underscore.js",
                    "scripts/base/moment.js",
                    "scripts/base/jquery.timeago.js",
                    "scripts/base/soundmanager2-nodebug.js",
                    "scripts/shared/jquery-extensions.js",
                    "scripts/embed/*.coffee"
                ];
            }
        }

        throw new Exception();
    }

    private static function getStylesForArea($area)
    {
        if ($area == 'app') {
            return [
                "styles/base/jquery-ui.css",
                "styles/base/colorbox.css",
                "styles/app.less",
                "styles/profiler.less"
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
