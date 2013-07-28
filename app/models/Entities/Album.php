<?php

	namespace Entities;

	use Cover;
	use Whoops\Example\Exception;

	class Album extends \Eloquent {
		protected $softDelete = true;

		public static function summary() {
			return self::select('id', 'title', 'user_id', 'slug', 'created_at', 'cover_id');
		}

		protected $table = 'albums';

		public function user() {
			return $this->belongsTo('Entities\User');
		}

		public function cover() {
			return $this->belongsTo('Entities\Image');
		}

		public function hasCover() {
			return $this->cover_id != null;
		}

		public function getCoverUrl($type = Image::NORMAL) {
			if (!$this->hasCover())
				return $this->user->getAvatarUrl($type);

			return $this->cover->getUrl($type);
		}

		public function getDirectory() {
			$dir = (string) ( floor( $this->id / 100 ) * 100 );
			return \Config::get('app.files_directory') . '/tracks/' . $dir;
		}

		public function getDates() {
			return ['created_at', 'deleted_at', 'published_at'];
		}

		public function getFilenameFor($format) {
			if (!isset(Track::$Formats[$format]))
				throw new Exception("$format is not a valid format!");

			$format = Track::$Formats[$format];
			return "{$this->id}.{$format['extension']}.zip";
		}
	}