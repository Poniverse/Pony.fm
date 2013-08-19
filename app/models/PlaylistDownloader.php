<?php

	class PlaylistDownloader {
		private $_playlist;
		private $_format;

		function __construct($playlist, $format) {
			$this->_playlist = $playlist;
			$this->_format = $format;
		}

		function download() {
			$zip = new ZipStream($this->_playlist->user->display_name . ' - ' . $this->_playlist->title . '.zip');
			$zip->setComment(
				'Album: '	. $this->_playlist->title ."\r\n".
				'Artist: '	. $this->_playlist->user->display_name ."\r\n".
				'URL: '		. $this->_playlist->url ."\r\n"."\r\n".
				'Downloaded on '. date('l, F jS, Y, \a\t h:i:s A') . '.'
			);

			$directory = $this->_playlist->user->display_name . '/' . $this->_playlist->title . '/';

			$notes =
				'Album: '	. $this->_playlist->title ."\r\n".
				'Artist: '	. $this->_playlist->user->display_name ."\r\n".
				'URL: '		. $this->_playlist->url ."\r\n".
				"\r\n".
				$this->_playlist->description ."\r\n".
				"\r\n".
				"\r\n".
				'Tracks' ."\r\n".
				"\r\n";

			foreach ($this->_playlist->tracks as $track) {
				if (!$track->is_downloadable)
					continue;

				$zip->addLargeFile($track->getFileFor($this->_format), $directory . $track->getDownloadFilenameFor($this->_format));
				$notes .=
					$track->track_number . '. ' . $track->title ."\r\n".
					$track->description ."\r\n".
					"\r\n";
			}

			$zip->addFile($notes, $directory . 'Album Notes.txt');
			$zip->finalize();
		}
	}