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

namespace App;

use App\Models\Playlist;
use App\Models\Track;
use App\Models\User;
use ZipStream;

class PlaylistDownloader
{
    /**
     * @var Playlist
     */
    private $_playlist;

    /**
     * @var string
     */
    private $_format;

    public function __construct($playlist, $format)
    {
        $this->_playlist = $playlist;
        $this->_format = $format;
    }

    public function download(User $user)
    {
        // Check whether the format is lossless yet not all master files are lossless
        $isLosslessFormatWithLossyTracks =  in_array($this->_format, Track::$LosslessFormats)
            && !$this->_playlist->hasLosslessTracksOnly()
            && $this->_playlist->hasLosslessTracks();

        $zip = new ZipStream($this->_playlist->user->display_name.' - '.$this->_playlist->title.'.zip');
        $zip->setComment(
            'Playlist: '.$this->_playlist->title."\r\n".
            'Curator: '.$this->_playlist->user->display_name."\r\n".
            'URL: '.$this->_playlist->url."\r\n"."\r\n".
            'Downloaded on '.date('l, F jS, Y, \a\t h:i:s A').'.'
        );

        $notes =
            'Playlist: '.$this->_playlist->title."\r\n".
            'Curator: '.$this->_playlist->user->display_name."\r\n".
            'URL: '.$this->_playlist->url."\r\n".
            "\r\n".
            $this->_playlist->description."\r\n".
            "\r\n".
            "\r\n".
            'Tracks'."\r\n".
            "\r\n";

        $m3u = '';
        $index = 1;
        foreach ($this->_playlist->tracks as $track) {
            if (!$track->is_downloadable && !$user->hasRole('admin')) {
                continue;
            }

            if ($isLosslessFormatWithLossyTracks && $track->isMasterLossy()) {
                $masterFormatName = $track->getMasterFormatName();
                $trackTarget = $track->downloadDirectory . '/' . $track->getDownloadFilenameFor($masterFormatName);
                $zip->addLargeFile($track->getFileFor($masterFormatName), $trackTarget);
            } else {
                $trackTarget = $track->downloadDirectory . '/' . $track->getDownloadFilenameFor($this->_format);
                $zip->addLargeFile($track->getFileFor($this->_format), $trackTarget);
            }
            
            $notes .=
                $index.'. '.$track->title."\r\n".
                $track->description."\r\n".
                "\r\n";

            $m3u .= '#EXTINF:'.$track->duration.','.$track->title."\r\n";
            $m3u .= '../'.$trackTarget."\r\n";

            $index++;
        }

        $playlistDir = 'Pony.fm Playlists/';
        $zip->addFile($notes, $playlistDir.$this->_playlist->title.'.txt');
        $zip->addFile($m3u, $playlistDir.$this->_playlist->title.'.m3u');
        $zip->finalize();
    }
}
