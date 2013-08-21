<?php

	namespace Entities;
	use Helpers;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\Cache;
	use Illuminate\Support\Facades\Log;
	use Illuminate\Support\Facades\URL;
	use Traits\SlugTrait;

	class Playlist extends \Eloquent {
		protected $table = 'playlists';
		protected $softDelete = true;

		use SlugTrait;

		public static function summary() {
			return self::select('id', 'title', 'user_id', 'slug', 'created_at', 'is_public', 'description', 'comment_count', 'download_count', 'view_count', 'favourite_count');
		}

		public function scopeDetails($query) {
			if (Auth::check()) {
				$query->with(['users' => function($query) {
					$query->whereUserId(Auth::user()->id);
				}]);
			}

			return !$query;
		}

		public static function mapPublicPlaylistShow($playlist) {
			$tracks = [];
			foreach ($playlist->tracks as $track) {
				$tracks[] = Track::mapPublicTrackSummary($track);
			}

			$formats = [];
			foreach (Track::$Formats as $name => $format) {
				$formats[] = [
					'name' => $name,
					'extension' => $format['extension'],
					'url' => $playlist->getDownloadUrl($name),
					'size' => Helpers::formatBytes($playlist->getFilesize($name))
				];
			}

			$comments = [];
			foreach ($playlist->comments as $comment) {
				$comments[] = Comment::mapPublic($comment);
			}

			$data = self::mapPublicPlaylistSummary($playlist);
			$data['tracks'] = $tracks;
			$data['comments'] = $comments;
			$data['formats'] = $formats;

			return $data;
		}

		public static function mapPublicPlaylistSummary($playlist) {
			$userData = [
				'stats' => [
					'views' => 0,
					'downloads' => 0
				],
				'is_favourited' => false
			];

			if ($playlist->users->count()) {
				$userRow = $playlist->users[0];
				$userData = [
					'stats' => [
						'views' => $userRow->view_count,
						'downloads' => $userRow->download_count,
					],
					'is_favourited' => $userRow->is_favourited
				];
			}

			return [
				'id' => $playlist->id,
				'track_count' => $playlist->tracks()->count(),
				'title' => $playlist->title,
				'slug' => $playlist->slug,
				'created_at' => $playlist->created_at,
				'is_public' => $playlist->is_public,
				'stats' => [
					'views' => $playlist->view_count,
					'downloads' => $playlist->download_count,
					'comments' => $playlist->comment_count,
					'favourites' => $playlist->favourite_count
				],
				'covers' => [
					'small' => $playlist->getCoverUrl(Image::SMALL),
					'normal' => $playlist->getCoverUrl(Image::NORMAL)
				],
				'url' => $playlist->url,
				'user' => [
					'id' => $playlist->user->id,
					'name' => $playlist->user->display_name,
					'url' => $playlist->user->url,
				],
				'user_data' => $userData
			];
		}

		public function tracks() {
			return $this
				->belongsToMany('Entities\Track')
				->withPivot('position')
				->withTimestamps()
				->orderBy('position', 'asc');
		}

		public function users() {
			return $this->hasMany('Entities\ResourceUser');
		}

		public function comments(){
			return $this->hasMany('Entities\Comment');
		}

		public function pins() {
			return $this->hasMany('Entities\PinnedPlaylist');
		}

		public function user() {
			return $this->belongsTo('Entities\User');
		}

		public function hasPinFor($userId) {
			foreach ($this->pins as $pin) {
				if ($pin->user_id == $userId)
					return true;
			}

			return false;
		}

		public function canView($user) {
			return $this->is_public || ($user != null && $user->id == $this->user_id);
		}

		public function getUrlAttribute() {
			return URL::to('/playlist/' . $this->id . '-' . $this->slug);
		}

		public function getDownloadUrl($format) {
			return URL::to('p' . $this->id . '/dl.' . Track::$Formats[$format]['extension']);
		}

		public function getFilesize($format) {
			$tracks = $this->tracks;
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
			if ($this->tracks->count() == 0)
				return $this->user->getAvatarUrl($type);

			return $this->tracks[0]->getCoverUrl($type);
		}

		public function pin($userId) {
			$pin = new PinnedPlaylist();
			$pin->playlist_id = $this->id;
			$pin->user_id = $userId;
			$pin->save();
		}

		private function getCacheKey($key) {
			return 'playlist-' . $this->id . '-' . $key;
		}
	}