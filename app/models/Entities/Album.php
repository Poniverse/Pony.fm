<?php

	namespace Entities;

	use Cover;
	use Helpers;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\Cache;
	use Illuminate\Support\Facades\URL;
	use Whoops\Example\Exception;
	use Traits\SlugTrait;

	class Album extends \Eloquent {
		protected $softDelete = true;

		use SlugTrait;

		public static function summary() {
			return self::select('id', 'title', 'user_id', 'slug', 'created_at', 'cover_id');
		}

		public function scopeDetails($query) {
			if (Auth::check()) {
				$query->with(['favourites' => function($query) {
					$query->whereUserId(Auth::user()->id);
				}]);
			}

			return !$query;
		}

		protected $table = 'albums';

		public function user() {
			return $this->belongsTo('Entities\User');
		}

		public function favourites() {
			return $this->hasMany('Entities\Favourite');
		}

		public function cover() {
			return $this->belongsTo('Entities\Image');
		}

		public function tracks() {
			return $this->hasMany('Entities\Track')->orderBy('track_number', 'asc');
		}

		public function comments(){
			return $this->hasMany('Entities\Comment');
		}

		public static function mapPublicAlbumShow($album) {
			$tracks = [];
			foreach ($album->tracks as $track) {
				$tracks[] = Track::mapPublicTrackSummary($track);
			}

			$formats = [];
			foreach (Track::$Formats as $name => $format) {
				$formats[] = [
					'name' => $name,
					'extension' => $format['extension'],
					'url' => $album->getDownloadUrl($name),
					'size' => Helpers::formatBytes($album->getFilesize($name))
				];
			}

			$comments = [];
			foreach ($album->comments as $comment) {
				$comments[] = Comment::mapPublic($comment);
			}

			$data = self::mapPublicAlbumSummary($album);
			$data['tracks'] = $tracks;
			$data['comments'] = ['count' => count($comments), 'list' => $comments];
			$data['formats'] = $formats;
			$data['stats'] = [
				'views' => 0,
				'downloads' => 0
			];

			return $data;
		}

		public static function mapPublicAlbumSummary($album) {
			return [
				'id' => $album->id,
				'track_count' => $album->tracks->count(),
				'title' => $album->title,
				'slug' => $album->slug,
				'created_at' => $album->created_at,
				'covers' => [
					'small' => $album->getCoverUrl(Image::SMALL),
					'normal' => $album->getCoverUrl(Image::NORMAL)
				],
				'url' => $album->url,
				'user' => [
					'id' => $album->user->id,
					'name' => $album->user->display_name,
					'url' => $album->user->url,
				],
				'is_favourited' => $album->favourites->count() > 0
			];
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

		public function getFilesize($format) {
			$tracks = $this->tracks()->get();
			if (!count($tracks))
				return 0;

			return Cache::remember($this->getCacheKey('filesize-' . $format), 1440, function() use ($tracks, $format) {
				$size = 0;
				foreach ($tracks as $track) {
					$size += $track->getFilesize($format);
				}

				return $size;
			});
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

			foreach (Track::$Formats as $name => $format) {
				Cache::forget($this->getCacheKey('filesize' . $name));
			}
		}

		private function getCacheKey($key) {
			return 'album-' . $this->id . '-' . $key;
		}
	}