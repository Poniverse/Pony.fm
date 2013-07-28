<?php

	namespace Entities;

	use Cover;
	use Whoops\Example\Exception;

	class Track extends \Eloquent {
		protected $softDelete = true;

		public static $Formats = [
			'FLAC' 		 => ['extension' => 'flac', 	'tag_format' => 'metaflac', 		'mime_type' => 'audio/flac', 'command' => 'ffmpeg 2>&1 -y -i {$source} -acodec flac -aq 8 -f flac {$target}'],
			'MP3' 		 => ['extension' => 'mp3', 		'tag_format' => 'id3v2.3', 			'mime_type' => 'audio/mpeg', 'command' => 'ffmpeg 2>&1 -y -i {$source} -acodec libmp3lame -ab 320k -f mp3 {$target}'],
			'OGG Vorbis' => ['extension' => 'ogg', 		'tag_format' => 'vorbiscomment',	'mime_type' => 'audio/ogg',  'command' => 'ffmpeg 2>&1 -y -i {$source} -acodec libvorbis -aq 7 -f ogg {$target}'],
			'AAC'  		 => ['extension' => 'm4a', 		'tag_format' => 'AtomicParsley', 	'mime_type' => 'audio/mp4',  'command' => 'ffmpeg 2>&1 -y -i {$source} -acodec libfaac -ab 256k -f mp4 {$target}'],
			'ALAC' 		 => ['extension' => 'alac.m4a', 'tag_format' => 'AtomicParsley', 	'mime_type' => 'audio/mp4',  'command' => 'ffmpeg 2>&1 -y -i {$source} -acodec alac {$target}'],
		];

		public static function summary() {
			return self::select('id', 'title', 'user_id', 'slug', 'is_vocal', 'is_explicit', 'created_at', 'published_at', 'duration', 'is_downloadable', 'genre_id', 'track_type_id', 'cover_id');
		}

		protected $table = 'tracks';

		public function ensureDirectoryExists() {
			$destination = $this->getDirectory();

			if (!is_dir($destination))
				mkdir($destination, 755);
		}

		public function hasCover() {
			return $this->cover_id != null;
		}

		public function cover() {
			return $this->belongsTo('Entities\Image');
		}

		public function showSongs() {
			return $this->belongsToMany('Entities\ShowSong');
		}

		public function isPublished() {
			return $this->published_at != null && $this->deleted_at == null;
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
			return ['created_at', 'deleted_at', 'published_at', 'released_at'];
		}

		public function user() {
			return $this->belongsTo('Entities\User');
		}

		public function getFilenameFor($format) {
			if (!isset(self::$Formats[$format]))
				throw new Exception("$format is not a valid format!");

			$format = self::$Formats[$format];
			return "{$this->id}.{$format['extension']}";
		}
	}