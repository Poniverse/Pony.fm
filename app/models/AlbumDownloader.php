<?php

	class AlbumDownloader {
		private $_album;
		private $_format;

		function __construct($album, $format) {
			$this->_album = $album;
			$this->_format = $format;
		}

		function download() {
			$zip = new ZipStream($this->_album->user->display_name . ' - ' . $this->_album->title . '.zip');
			$zip->setComment(
				'Album: '	. $this->_album->title ."\r\n".
				'Artist: '	. $this->_album->user->display_name ."\r\n".
				'URL: '		. $this->_album->url ."\r\n"."\r\n".
				'Downloaded on '. date('l, F jS, Y, \a\t h:i:s A') . '.'
			);

			$directory = $this->_album->user->display_name . '/' . $this->_album->title . '/';

			$notes =
				'Album: '	. $this->_album->title ."\r\n".
				'Artist: '	. $this->_album->user->display_name ."\r\n".
				'URL: '		. $this->_album->url ."\r\n".
				"\r\n".
				$this->_album->description ."\r\n".
				"\r\n".
				"\r\n".
				'Tracks' ."\r\n".
				"\r\n";

			foreach ($this->_album->tracks as $track) {
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