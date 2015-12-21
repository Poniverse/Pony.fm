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

namespace Poniverse\Ponyfm;

use Config;
use Helpers;
use Illuminate\Database\Eloquent\Model;
use App;
use File;

class TrackFile extends Model
{
    // used for the "status" property
    const STATUS_NOT_BEING_PROCESSED = 0;
    const STATUS_PROCESSING = 1;
    const STATUS_PROCESSING_ERROR = 2;


    public function track()
    {
        return $this->belongsTo('Poniverse\Ponyfm\Track')->withTrashed();
    }

    /**
     * Look up and return a TrackFile by track ID and an extension.
     *
     * If the track does not have a TrackFile in the given extension's format, a 404 exception is thrown.
     *
     * @param int $trackId
     * @param string $extension
     * @return TrackFile
     */
    public static function findOrFailByExtension($trackId, $extension)
    {
        // find the extension's format
        $requestedFormatName = null;
        foreach (Track::$Formats as $name => $format) {
            if ($extension === $format['extension']) {
                $requestedFormatName = $name;
                break;
            }
        }
        if ($requestedFormatName === null) {
            App::abort(404);
        }

        $trackFile = static::
        with('track')
            ->where('track_id', $trackId)
            ->where('format', $requestedFormatName)
            ->first();

        if ($trackFile === null) {
            App::abort(404);
        } else {
            return $trackFile;
        }
    }

    public function getFormatAttribute($value)
    {
        return $value;
    }

    public function getExtensionAttribute()
    {
        return Track::$Formats[$this->format]['extension'];
    }

    public function getUrlAttribute()
    {
        return action('TracksController@getDownload', ['id' => $this->track_id, 'extension' => $this->extension]);
    }

    public function getSizeAttribute()
    {
        return Helpers::formatBytes($this->getFilesize());
    }

    public function getFormat()
    {
        return Track::$Formats[$this->format];
    }

    protected function getFilesize()
    {
        return $this->filesize;
    }

    public function getDirectory()
    {
        $dir = (string)(floor($this->track_id / 100) * 100);

        return \Config::get('ponyfm.files_directory') . '/tracks/' . $dir;
    }

    public function getFile()
    {
        return "{$this->getDirectory()}/{$this->track_id}.{$this->extension}";
    }

    public function getFilename()
    {
        return "{$this->track_id}.{$this->extension}";
    }

    public function getDownloadFilename()
    {
        return "{$this->track->title}.{$this->extension}";
    }

    private function getCacheKey($key)
    {
        return 'track_file-' . $this->id . '-' . $key;
    }

    /**
     * If this file exists, update its estimated filesize in the database.
     *
     * @return int $size
     */
    public function updateFilesize()
    {
        $file = $this->getFile();

        if (File::exists($file)) {
            $size = File::size($file);

            $this->filesize = $size;
            $this->update();
        }

        return $this->filesize;
    }
}
