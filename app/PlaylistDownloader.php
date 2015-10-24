<?php

namespace Poniverse\Ponyfm;

use ZipStream;

class PlaylistDownloader
{
    private $_playlist;
    private $_format;

    function __construct($playlist, $format)
    {
        $this->_playlist = $playlist;
        $this->_format = $format;
    }

    function download()
    {
        $zip = new ZipStream($this->_playlist->user->display_name . ' - ' . $this->_playlist->title . '.zip');
        $zip->setComment(
            'Playlist: ' . $this->_playlist->title . "\r\n" .
            'Curator: ' . $this->_playlist->user->display_name . "\r\n" .
            'URL: ' . $this->_playlist->url . "\r\n" . "\r\n" .
            'Downloaded on ' . date('l, F jS, Y, \a\t h:i:s A') . '.'
        );

        $notes =
            'Playlist: ' . $this->_playlist->title . "\r\n" .
            'Curator: ' . $this->_playlist->user->display_name . "\r\n" .
            'URL: ' . $this->_playlist->url . "\r\n" .
            "\r\n" .
            $this->_playlist->description . "\r\n" .
            "\r\n" .
            "\r\n" .
            'Tracks' . "\r\n" .
            "\r\n";

        $m3u = '';
        $index = 1;
        foreach ($this->_playlist->tracks as $track) {
            if (!$track->is_downloadable) {
                continue;
            }

            $trackTarget = $track->downloadDirectory . '/' . $track->getDownloadFilenameFor($this->_format);
            $zip->addLargeFile($track->getFileFor($this->_format), $trackTarget);
            $notes .=
                $index . '. ' . $track->title . "\r\n" .
                $track->description . "\r\n" .
                "\r\n";

            $m3u .= '#EXTINF:' . $track->duration . ',' . $track->title . "\r\n";
            $m3u .= '../' . $trackTarget . "\r\n";

            $index++;
        }

        $playlistDir = 'Pony.fm Playlists/';
        $zip->addFile($notes, $playlistDir . $this->_playlist->title . '.txt');
        $zip->addFile($m3u, $playlistDir . $this->_playlist->title . '.m3u');
        $zip->finalize();
    }
}
