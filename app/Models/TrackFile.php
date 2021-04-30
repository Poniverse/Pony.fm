<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Feld0.
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

namespace App\Models;

use App\Http\Controllers\TracksController;
use Helpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

/**
 * App\Models\TrackFile.
 *
 * @property int $id
 * @property int $track_id
 * @property bool $is_master
 * @property string $format
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property bool $is_cacheable
 * @property bool $status
 * @property \Carbon\Carbon $expires_at
 * @property int $filesize
 * @property-read \App\Models\Track $track
 * @property-read mixed $extension
 * @property-read mixed $url
 * @property-read mixed $size
 * @property-read mixed $is_expired
 * @property int $version
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TrackFile whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TrackFile whereTrackId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TrackFile whereIsMaster($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TrackFile whereFormat($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TrackFile whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TrackFile whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TrackFile whereIsCacheable($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TrackFile whereStatus($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TrackFile whereExpiresAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TrackFile whereFilesize($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\TrackFile whereVersion($value)
 * @mixin \Eloquent
 */
class TrackFile extends Model
{
    // used for the "status" property
    const STATUS_NOT_BEING_PROCESSED = 0;
    const STATUS_PROCESSING = 1;
    const STATUS_PROCESSING_ERROR = 2;
    const STATUS_PROCESSING_PENDING = 3;

    protected $appends = ['is_expired'];

    protected $casts = [
        'expires_at' => 'datetime',
        'id'            => 'integer',
        'track_id'      => 'integer',
        'is_master'     => 'boolean',
        'format'        => 'string',
        'is_cacheable'  => 'boolean',
        'status'        => 'integer',
        'filesize'      => 'integer',
    ];

    public function track()
    {
        return $this->belongsTo(Track::class)->withTrashed();
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
        $track = Track::find($trackId);
        if (! $track) {
            abort(404);
        }

        // find the extension's format
        $requestedFormatName = null;
        foreach (Track::$Formats as $name => $format) {
            if ($extension === $format['extension']) {
                $requestedFormatName = $name;
                break;
            }
        }
        if ($requestedFormatName === null) {
            abort(404);
        }

        $trackFile = static::
        with('track')
            ->where('track_id', $trackId)
            ->where('format', $requestedFormatName)
            ->where('version', $track->current_version)
            ->first();

        if ($trackFile === null) {
            abort(404);
        } else {
            return $trackFile;
        }
    }

    public function getIsExpiredAttribute()
    {
        return  $this->attributes['expires_at'] === null ||
                $this->expires_at->isPast();
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
        return action([TracksController::class, 'getDownload'], ['id' => $this->track_id, 'extension' => $this->extension]);
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
        $dir = (string) (floor($this->track_id / 100) * 100);

        return config('ponyfm.files_directory').'/tracks/'.$dir;
    }

    public function getFile()
    {
        return "{$this->getDirectory()}/{$this->track_id}-v{$this->version}.{$this->extension}";
    }

    public function getUnversionedFile()
    {
        return "{$this->getDirectory()}/{$this->track_id}.{$this->extension}";
    }

    public function getFilename()
    {
        return "{$this->track_id}-v{$this->track->current_version}.{$this->extension}";
    }

    public function getUnversionedFilename()
    {
        return "{$this->track_id}.{$this->extension}";
    }

    public function getDownloadFilename()
    {
        return "{$this->track->title}.{$this->extension}";
    }

    private function getCacheKey($key)
    {
        return 'track_file-'.$this->id.'-'.$key;
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

    public function isLossy() : bool
    {
        return ! in_array($this->format, Track::$LosslessFormats);
    }
}
