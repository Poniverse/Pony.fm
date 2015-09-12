<?php

class Assets
{
    public static function scriptIncludes($area = 'app')
    {
        if (!Config::get("app.debug")) {
            return '<script src="/build/scripts/' . $area . '.js?' . filemtime("./build/scripts/" . $area . ".js") . '"></script>';
        }

        $scripts = self::mergeGlobs(self::getScriptsForArea($area));
        $retVal = "";

        foreach ($scripts as $script) {
            $filename = self::replaceExtensionWith($script, ".coffee", ".js");
            $retVal .= "<script src='/build/$filename?" . filemtime('./build/' . $filename) . "'></script>";
        }

        return $retVal;
    }

    public static function styleIncludes($area = 'app')
    {
        if (!Config::get("app.debug")) {
            return '<script>document.write(\'<link rel="stylesheet" href="build/styles/' . $area . '.css?' . filemtime("build/styles/" . $area . ".css") . '" />\');</script>';
        }

        $styles = self::mergeGlobs(self::getStylesForArea($area));
        $retVal = "";

        foreach ($styles as $style) {
            $filename = self::replaceExtensionWith($style, ".less", ".css");
            $retVal .= "<link rel='stylesheet' href='/build/$filename?" . filemtime('./build/' . $filename) . "' />";
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
                    "scripts/base/jquery.viewport.js",
                    "scripts/base/underscore.js",
                    "scripts/base/moment.js",
                    "scripts/base/jquery.timeago.js",
                    "scripts/base/soundmanager2-nodebug.js",
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