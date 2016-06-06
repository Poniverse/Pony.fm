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
use Illuminate\Database\Eloquent\Relations\Relation;
use Poniverse\Ponyfm\Jobs\EncodeTrackFile;
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
    public function countDownloadableTracks($format) {
        return $this->downloadableTrackFiles($format)->count();
    }


    /**
     * Returns the number of currently-available track files (master files +
     * currently cached files) for this collection in the given format.
     *
     * @param string $format
     * @return int
     */
    public function countAvailableTrackFiles($format) {
        $trackFiles = $this->downloadableTrackFiles($format);
        $availableCount = 0;

        foreach ($trackFiles as $trackFile) {
            /** @var TrackFile $trackFile */

            if (
                $trackFile->is_master ||
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
    public function encodeCacheableTrackFiles($format) {
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
    protected function downloadableTrackFiles($format) {
        return $this->trackFiles()->with([
            'track' => function($query) {
                $query->where('is_downloadable', true);
            }
        ])->where('format', $format)->get();
    }
}
