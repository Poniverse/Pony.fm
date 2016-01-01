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

use Poniverse\Ponyfm\Models\Album;
use ZipStream;

class AlbumDownloader
{
    /**
     * @var Album
     */
    private $_album;

    /**
     * @var string
     */
    private $_format;

    function __construct($album, $format)
    {
        $this->_album = $album;
        $this->_format = $format;
    }

    function download()
    {
        $zip = new ZipStream($this->_album->user->display_name . ' - ' . $this->_album->title . '.zip');
        $zip->setComment(
            'Album: ' . $this->_album->title . "\r\n" .
            'Artist: ' . $this->_album->user->display_name . "\r\n" .
            'URL: ' . $this->_album->url . "\r\n" . "\r\n" .
            'Downloaded on ' . date('l, F jS, Y, \a\t h:i:s A') . '.'
        );

        $directory = $this->_album->user->display_name . '/' . $this->_album->title . '/';

        $notes =
            'Album: ' . $this->_album->title . "\r\n" .
            'Artist: ' . $this->_album->user->display_name . "\r\n" .
            'URL: ' . $this->_album->url . "\r\n" .
            "\r\n" .
            $this->_album->description . "\r\n" .
            "\r\n" .
            "\r\n" .
            'Tracks' . "\r\n" .
            "\r\n";

        foreach ($this->_album->tracks as $track) {
            if (!$track->is_downloadable) {
                continue;
            }

            $zip->addLargeFile($track->getFileFor($this->_format),
                $directory . $track->getDownloadFilenameFor($this->_format));
            $notes .=
                $track->track_number . '. ' . $track->title . "\r\n" .
                $track->description . "\r\n" .
                "\r\n";
        }

        $zip->addFile($notes, $directory . 'Album Notes.txt');
        $zip->finalize();
    }
}
