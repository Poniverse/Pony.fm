<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Peter Deltchev
 * Copyright (C) 2015 Kelvin Zhang
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

namespace Poniverse\Ponyfm\Traits;

use File;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Cache;
use Poniverse\Ponyfm\Jobs\EncodeTrackFile;
use Poniverse\Ponyfm\Models\Track;
use Poniverse\Ponyfm\Models\TrackFile;

/**
 * Class TrackCollection
 * @package Poniverse\Ponyfm\Traits
 *
 * Contains common logic between albums and playlists. They share some functionality
 * because they're both a form of downloadable track collection.
 */
trait TrackCollection
{
    /**
     * This relation represents all tracks contained by the collection.
     *
     * @return Relation
     */
    abstract public function tracks();

    /**
     * This relation represents all track files belonging to this collection's
     * tracks.
     *
     * @return Relation
     */
    abstract public function trackFiles();


    /**
     * Returns the number of tracks that are available in the given format.
     *
     * @param string $format
     * @return int the number of downloadable tracks in this collection
     */
    public function countDownloadableTracks($format)
    {
        return $this->downloadableTrackFiles($format)->count();
    }


    /**
     * Returns the number of currently-available track files (master files +
     * currently cached files) for this collection in the given format.
     *
     * @param string $format
     * @return int
     */
    public function countAvailableTrackFiles($format)
    {
        $trackFiles = $this->downloadableTrackFiles($format);
        $availableCount = 0;

        foreach ($trackFiles as $trackFile) {
            /** @var TrackFile $trackFile */

            if ($trackFile->is_master ||
                ($trackFile->expires_at != null && File::exists($trackFile->getFile()))
            ) {
                $availableCount++;
            }
        }

        return $availableCount;
    }


    /**
     * Kicks off the encoding of any cacheable files in this collection that
     * do not currently exist.
     *
     * @param $format
     */
    public function encodeCacheableTrackFiles($format)
    {
        $trackFiles = $this->downloadableTrackFiles($format);

        foreach ($trackFiles as $trackFile) {
            /** @var TrackFile $trackFile */
            if (!File::exists($trackFile->getFile()) && $trackFile->status == TrackFile::STATUS_NOT_BEING_PROCESSED) {
                $this->dispatch(new EncodeTrackFile($trackFile, true));
            }
        }
    }


    /**
     * Returns an Eloquent collection of downloadable TrackFiles for this {@link TrackCollection}.
     * A {@link TrackFile} is considered downloadable if its associated {@link Track} is.
     *
     * @param $format
     * @return Collection
     */
    protected function downloadableTrackFiles($format)
    {
        return $this->trackFiles()->with([
            'track' => function ($query) {
                $query->where('is_downloadable', true);
            }
        ])->where('format', $format)->get();
    }

    /**
     * Returns a boolean based on whether at least one (@link TrackFile)
     * for this (@link TrackCollection)'s tracks has a lossless master file.
     *
     * @return bool
     */
    public function hasLosslessTracks() : bool
    {
        $hasLosslessTracks = false;
        foreach ($this->tracks as $track) {
            if (!$track->isMasterLossy()) {
                $hasLosslessTracks = true;
                break;
            }
        }
        return $hasLosslessTracks;
    }

    /**
     * Returns a boolean based on whether all (@link TrackFile)s
     * for this (@link TrackCollection)'s tracks have lossless master files.
     *
     * @return bool
     */
    public function hasLosslessTracksOnly() : bool
    {
        $hasLosslessTracksOnly = true;
        foreach ($this->tracks as $track) {
            if ($track->isMasterLossy()) {
                $hasLosslessTracksOnly = false;
                break;
            }
        }
        return $hasLosslessTracksOnly;
    }

    /**
     * Gets the filesize in bytes for a (@link Album) or (@link Playlist) based on a format.
     *
     * @param $format
     * @return int
     */
    public function getFilesize($format) : int
    {
        $tracks = $this->tracks;
        if (!count($tracks)) {
            return 0;
        }

        return Cache::remember($this->getCacheKey('filesize-'.$format), 1440, function () use ($tracks, $format) {
            $size = 0;

            // Check whether the format is lossless yet not all master files are lossless
            $isLosslessFormatWithLossyTracks =  in_array($format, Track::$LosslessFormats)
                && !$this->hasLosslessTracksOnly()
                && $this->hasLosslessTracks();
            
            foreach ($tracks as $track) {
                /** @var $track Track */

                // Ensure that only downloadable tracks are added onto the file size
                if (!$track->is_downloadable) {
                    continue;
                }

                try {
                    // Get the file size corresponding to the losslessness of the track master file and format specified
                    if ($isLosslessFormatWithLossyTracks && $track->isMasterLossy()) {
                        $size += $track->getFilesize($track->getMasterFormatName());
                    } else {
                        $size += $track->getFilesize($format);
                    }
                } catch (TrackFileNotFoundException $e) {
                    // do nothing - this track won't be included in the download
                }
            }

            return $size;
        });
    }
}
