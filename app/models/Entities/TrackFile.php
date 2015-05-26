<?php namespace Entities;

use Helpers;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Cache;


class TrackFile extends \Eloquent {
	public function track() {
		return $this->belongsTo('Entities\Track');
	}

	public function getFormatAttribute($value) {
		return $value;
	}

	public function getExtensionAttribute() {
		return Track::$Formats[$this->format]['extension'];
	}

	public function getUrlAttribute() {
		return URL::to('/t' . $this->track_id . '/dl.' . $this->extension);
	}

	public function getSizeAttribute($value) {
		return Helpers::formatBytes($this->getFilesize($this->getFile()));
	}

	public function getFormat() {
		return Track::$Formats[$this->format];
	}

	protected function getFilesize() {
		return Cache::remember($this->getCacheKey('filesize'), 1440, function () {
			$file = $this->getFile();
			$size = 0;

			if (is_file($file)) {
				$size = filesize($file);
			}

			return $size;
		});
	}

	public function getDirectory() {
		$dir = (string) (floor($this->track_id / 100) * 100);
		return \Config::get('app.files_directory') . '/tracks/' . $dir;
	}

	public function getFile() {
		return "{$this->getDirectory()}/{$this->track_id}.{$this->extension}";
	}

	public function getFilename() {
		return "{$this->track_id}.{$this->extension}";
	}

	public function getDownloadFilename() {
		return "{$this->track->title}.{$this->extension}";
	}

	private function getCacheKey($key) {
		return 'track_file-' . $this->id . '-' . $key;
	}
}