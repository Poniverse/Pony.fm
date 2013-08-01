<?php

	namespace Entities;

	use Cover;
	use Illuminate\Support\Facades\URL;
	use Whoops\Example\Exception;
	use Traits\SlugTrait;

	class Album extends \Eloquent {
		protected $softDelete = true;

		use SlugTrait;

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

		public function tracks() {
			return $this->hasMany('Entities\Track')->orderBy('track_number', 'asc');
		}

		public function hasCover() {
			return $this->cover_id != null;
		}

		public function getUrlAttribute() {
			return URL::to('albums/' . $this->id . '-' . $this->slug);
		}

		public function getDownloadUrl($format) {
			return URL::to('a' . $this->id . '/dl.' . Track::$Formats[$format]['extension']);
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

		public function updateTrackNumbers() {
			$tracks = Track::whereAlbumId($this->id)->get();
			$index = 1;

			foreach ($tracks as $track) {
				$track->track_number = $index;
				$index++;
				$track->updateTags();
				$track->save();
			}
		}

		public function syncTrackIds($trackIds) {
			$trackIdsInAlbum = [];
			foreach ($this->tracks as $track) {
				$trackIdsInAlbum[] = $track->id;
			}

			$trackIdsCount = count($trackIds);
			$trackIdsInAlbumCount = count($trackIdsInAlbum);
			$isSame = true;

			if ($trackIdsInAlbumCount != $trackIdsCount)
				$isSame = false;
			else
				for ($i = 0; $i < $trackIdsInAlbumCount; $i++) {
					if ($i >= $trackIdsCount || $trackIdsInAlbum[$i] != $trackIds[$i]) {
						$isSame = false;
						break;
					}
				}

			if ($isSame)
				return;

			$index = 1;
			$tracksToRemove = [];
			$albumsToFix = [];

			foreach ($this->tracks as $track)
				$tracksToRemove[$track->id] = $track;

			foreach ($trackIds as $trackId) {
				if (!strlen(trim($trackId)))
					continue;

				$track = Track::find($trackId);
				if ($track->album_id != null && $track->album_id != $this->id) {
					$albumsToFix[] = $track->album;
				}

				$track->album_id = $this->id;
				$track->track_number = $index;
				$track->updateTags();
				$track->save();

				unset($tracksToRemove[$track->id]);
				$index++;
			}

			foreach ($tracksToRemove as $track) {
				$track->album_id = null;
				$track->track_number = null;
				$track->updateTags();
				$track->save();
			}

			foreach ($albumsToFix as $album) {
				$album->updateTrackNumbers();
			}
		}
	}