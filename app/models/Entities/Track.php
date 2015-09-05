<?php

	namespace Entities;

	use Cover;
	use External;
	use getid3_writetags;
	use Helpers;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\Cache;
	use Illuminate\Support\Facades\Config;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Log;
	use Illuminate\Support\Facades\URL;
	use Illuminate\Support\Str;
	use Whoops\Example\Exception;
	use Traits\SlugTrait;

	class Track extends \Eloquent {
		protected $softDelete = true;

		use SlugTrait {
			SlugTrait::setTitleAttribute as setTitleAttributeSlug;
		}

		public static $Formats = [
			'FLAC' 		 => ['index' => 0, 'extension' => 'flac', 		'tag_format' => 'metaflac', 		'tag_method' => 'updateTagsWithGetId3', 'mime_type' => 'audio/flac', 'command' => 'ffmpeg 2>&1 -y -i {$source} -acodec flac -aq 8 -f flac {$target}'],
			'MP3' 		 => ['index' => 1, 'extension' => 'mp3', 		'tag_format' => 'id3v2.3', 			'tag_method' => 'updateTagsWithGetId3', 'mime_type' => 'audio/mpeg', 'command' => 'ffmpeg 2>&1 -y -i {$source} -acodec libmp3lame -ab 320k -f mp3 {$target}'],
			'OGG Vorbis' => ['index' => 2, 'extension' => 'ogg', 		'tag_format' => 'vorbiscomment',	'tag_method' => 'updateTagsWithGetId3', 'mime_type' => 'audio/ogg',  'command' => 'ffmpeg 2>&1 -y -i {$source} -acodec libvorbis -aq 7 -f ogg {$target}'],
			'AAC'  		 => ['index' => 3, 'extension' => 'm4a', 		'tag_format' => 'AtomicParsley', 	'tag_method' => 'updateTagsWithAtomicParsley', 'mime_type' => 'audio/mp4',  'command' => 'ffmpeg 2>&1 -y -i {$source} -acodec libfaac -ab 256k -f mp4 {$target}'],
			'ALAC' 		 => ['index' => 4, 'extension' => 'alac.m4a', 	'tag_format' => 'AtomicParsley', 	'tag_method' => 'updateTagsWithAtomicParsley', 'mime_type' => 'audio/mp4',  'command' => 'ffmpeg 2>&1 -y -i {$source} -acodec alac {$target}'],
		];

		public static function summary() {
			return self::select('id', 'title', 'user_id', 'slug', 'is_vocal', 'is_explicit', 'created_at', 'published_at', 'duration', 'is_downloadable', 'genre_id', 'track_type_id', 'cover_id', 'album_id', 'comment_count', 'download_count', 'view_count', 'play_count', 'favourite_count');
		}

		public function scopeUserDetails($query) {
			if (Auth::check()) {
				$query->with(['users' => function($query) {
					$query->whereUserId(Auth::user()->id);
				}]);
			}
		}

		public function scopePublished($query) {
			$query->whereNotNull('published_at');
		}

		public function scopeListed($query) {
			$query->whereIsListed(true);
		}

		public function scopeExplicitFilter($query) {
			if (!Auth::check() || !Auth::user()->can_see_explicit_content)
				$query->whereIsExplicit(false);
		}

		public function scopeWithComments($query) {
			$query->with(['comments' => function($query) { $query->with('user'); }]);
		}

		public static function popular($count, $allowExplicit = false) {
			$trackIds = Cache::remember('popular_tracks' . $count . '-' . ($allowExplicit ? 'explicit' : 'safe'), 5, function() use ($allowExplicit, $count) {
				$query = static
					::published()
					->listed()
					->join(DB::raw('
						(	SELECT `track_id`, `created_at`
							FROM `resource_log_items`
							WHERE track_id IS NOT NULL AND log_type = 3 AND `created_at` > now() - INTERVAL 1 DAY
						) AS ranged_plays'),
							'tracks.id', '=', 'ranged_plays.track_id')
					->groupBy('id')
					->orderBy('plays', 'desc')
					->take($count);

				if (!$allowExplicit)
					$query->whereIsExplicit(false);

				$results = [];

				foreach($query->get(['*', DB::raw('count(*) as plays')]) as $track) {
					$results[] = $track->id;
				}

				return $results;
			});

			if (!count($trackIds))
				return [];

			$tracks = Track::summary()
				->userDetails()
				->explicitFilter()
				->published()
				->with('user', 'genre', 'cover', 'album', 'album.user')
				->whereIn('id', $trackIds);

			$processed = [];
			foreach ($tracks->get() as $track)
				$processed[] = Track::mapPublicTrackSummary($track);

			return $processed;
		}

		public static function mapPublicTrackShow($track) {
			$returnValue = self::mapPublicTrackSummary($track);
			$returnValue['description'] = $track->description;
			$returnValue['lyrics'] = $track->lyrics;

			$comments = [];

			foreach ($track->comments as $comment) {
				$comments[] = Comment::mapPublic($comment);
			}

			$returnValue['comments'] = $comments;

			if ($track->album_id != null) {
				$returnValue['album'] = [
					'title' => $track->album->title,
					'url' => $track->album->url,
				];
			}

			$formats = [];

			foreach ($track->trackFiles as $trackFile) {
				$formats[] = [
					'name' => $trackFile->format,
					'extension' => $trackFile->extension,
					'url'   => $trackFile->url,
					'size'  => $trackFile->size
				];
			}

			$returnValue['share'] = [
				'url' => URL::to('/t' . $track->id),
				'html' => '<iframe src="' . URL::to('t' . $track->id . '/embed') . '" width="100%" height="150" allowTransparency="true" frameborder="0" seamless allowfullscreen></iframe>',
				'bbcode' => '[url=' . $track->url . '][img]' . $track->getCoverUrl() . '[/img][/url]',
				'twitterUrl' => 'https://platform.twitter.com/widgets/tweet_button.html?text=' . $track->title . ' by ' . $track->user->display_name . ' on Pony.fm'
			];

			$returnValue['share']['tumblrUrl'] = 'http://www.tumblr.com/share/video?embed=' . urlencode($returnValue['share']['html']) . '&caption=' . urlencode($track->title);

			$returnValue['formats'] = $formats;

			return $returnValue;
		}

		public static function mapPublicTrackSummary($track) {
			$userData = [
				'stats' => [
					'views' => 0,
					'plays' => 0,
					'downloads' => 0
				],
				'is_favourited' => false
			];

			if (Auth::check() && $track->users->count()) {
				$userRow = $track->users[0];
				$userData = [
					'stats' => [
						'views' => $userRow->view_count,
						'plays' => $userRow->play_count,
						'downloads' => $userRow->download_count,
					],
					'is_favourited' => $userRow->is_favourited
				];
			}

			return [
				'id' => $track->id,
				'title' => $track->title,
				'user' => [
					'id' => $track->user->id,
					'name' => $track->user->display_name,
					'url' => $track->user->url
				],
				'stats' => [
					'views' => $track->view_count,
					'plays' => $track->play_count,
					'downloads' => $track->download_count,
					'comments' => $track->comment_count,
					'favourites' => $track->favourite_count
				],
				'url' => $track->url,
				'slug' => $track->slug,
				'is_vocal' => $track->is_vocal,
				'is_explicit' => $track->is_explicit,
				'is_downloadable' => $track->is_downloadable,
				'is_published' => $track->isPublished(),
				'published_at' => $track->published_at,
				'duration' => $track->duration,
				'genre' => $track->genre != null
					?
					[
						'id' => $track->genre->id,
						'slug' => $track->genre->slug,
						'name' => $track->genre->name
					] : null,
				'track_type_id' => $track->track_type_id,
				'covers' => [
					'thumbnail' => $track->getCoverUrl(Image::THUMBNAIL),
					'small' => $track->getCoverUrl(Image::SMALL),
					'normal' => $track->getCoverUrl(Image::NORMAL)
				],
				'streams' => [
					'mp3' => $track->getStreamUrl('MP3'),
					'aac' => (!Config::get('app.debug') || is_file($track->getFileFor('AAC'))       ) ? $track->getStreamUrl('AAC') : null,
					'ogg' => ( Config::get('app.debug') || is_file($track->getFileFor('OGG Vorbis'))) ? $track->getStreamUrl('OGG Vorbis') : null
				],
				'user_data' => $userData,
				'permissions' => [
					'delete' => Auth::check() && Auth::user()->id == $track->user_id,
					'edit' => Auth::check() && Auth::user()->id == $track->user_id
				]
			];
		}

		public static function mapPrivateTrackShow($track) {
			$showSongs = [];
			foreach ($track->showSongs as $showSong) {
				$showSongs[] = ['id' => $showSong->id, 'title' => $showSong->title];
			}

			$returnValue = self::mapPrivateTrackSummary($track);
			$returnValue['album_id'] = $track->album_id;
			$returnValue['show_songs'] = $showSongs;
			$returnValue['real_cover_url'] = $track->getCoverUrl(Image::NORMAL);
			$returnValue['cover_url'] = $track->hasCover() ? $track->getCoverUrl(Image::NORMAL) : null;
			$returnValue['released_at'] = $track->released_at;
			$returnValue['lyrics'] = $track->lyrics;
			$returnValue['description'] = $track->description;
			$returnValue['is_downloadable'] = !$track->isPublished() ? true : (bool)$track->is_downloadable;
			$returnValue['license_id'] = $track->license_id != null ? $track->license_id : 3;
			return $returnValue;
		}

		public static function mapPrivateTrackSummary($track) {
			return [
				'id' => $track->id,
				'title' => $track->title,
				'user_id' => $track->user_id,
				'slug' => $track->slug,
				'is_vocal' => $track->is_vocal,
				'is_explicit' => $track->is_explicit,
				'is_downloadable' => $track->is_downloadable,
				'is_published' => $track->isPublished(),
				'created_at' => $track->created_at,
				'published_at' => $track->published_at,
				'duration' => $track->duration,
				'genre_id' => $track->genre_id,
				'track_type_id' => $track->track_type_id,
				'cover_url' => $track->getCoverUrl(Image::SMALL),
				'is_listed' => !!$track->is_listed
			];
		}

		protected $table = 'tracks';

		public function genre() {
			return $this->belongsTo('Entities\Genre');
		}

		public function trackType() {
			return $this->belongsTo('Entities\TrackType', 'track_type_id');
		}

		public function comments(){
			return $this->hasMany('Entities\Comment')->orderBy('created_at', 'desc');
		}

		public function favourites() {
			return $this->hasMany('Entities\Favourite');
		}

		public function cover() {
			return $this->belongsTo('Entities\Image');
		}

		public function showSongs() {
			return $this->belongsToMany('Entities\ShowSong');
		}

		public function users() {
			return $this->hasMany('Entities\ResourceUser');
		}

		public function user() {
			return $this->belongsTo('Entities\User');
		}

		public function album() {
			return $this->belongsTo('Entities\Album');
		}

		public function trackFiles() {
			return $this->hasMany('Entities\TrackFile');
		}

		public function getYearAttribute() {
			return date('Y', strtotime($this->getReleaseDate()));
		}

		public function setTitleAttribute($value) {
			$this->setTitleAttributeSlug($value);;
			$this->updateHash();
		}

		public function getFilesize($formatName) {
			return Cache::remember($this->getCacheKey('filesize-' . $formatName), 1440, function () use ($formatName) {
				$file = $this->getFileFor($formatName);
				$size = 0;

				if (is_file($file))
					$size = filesize($file);

				return $size;
			});
		}

		public function canView($user) {
			if ($this->isPublished())
				return true;

			return $this->user_id == $user->id;
		}

		public function getUrlAttribute() {
			return URL::to('/tracks/' . $this->id . '-' . $this->slug);
		}

		public function getDownloadDirectoryAttribute() {
			if ($this->album) {
				return $this->user->display_name . '/' . $this->album->title;
			}

			return $this->user->display_name;
		}

		public function getReleaseDate() {
			if($this->released_at !== NULL)
				return $this->released_at;

			if ($this->published_at !== NULL) {
				return Str::limit($this->published_at, 10, '');
			}

			return Str::limit($this->attributes['created_at'], 10, '');
		}

		public function ensureDirectoryExists() {
			$destination = $this->getDirectory();
			umask(0);

			if (!is_dir($destination))
				mkdir($destination, 777, true);
		}

		public function hasCover() {
			return $this->cover_id != null;
		}

		public function isPublished() {
			return $this->published_at != null && $this->deleted_at == null;
		}

		public function getCoverUrl($type = Image::NORMAL) {
			if (!$this->hasCover()) {
				if ($this->album_id != null)
					return $this->album->getCoverUrl($type);

				return $this->user->getAvatarUrl($type);
			}

			return $this->cover->getUrl($type);
		}

		public function getStreamUrl($format = 'MP3') {
			return URL::to('/t' . $this->id . '/stream.' . self::$Formats[$format]['extension']);
		}

		public function getDirectory() {
			$dir = (string) ( floor( $this->id / 100 ) * 100 );
			return \Config::get('app.files_directory') . '/tracks/' . $dir;
		}

		public function getDates() {
			return ['created_at', 'deleted_at', 'published_at', 'released_at'];
		}

		public function getFilenameFor($format) {
			if (!isset(self::$Formats[$format]))
				throw new Exception("$format is not a valid format!");

			$format = self::$Formats[$format];
			return "{$this->id}.{$format['extension']}";
		}

		public function getDownloadFilenameFor($format) {
			if (!isset(self::$Formats[$format]))
				throw new Exception("$format is not a valid format!");

			$format = self::$Formats[$format];
			return "{$this->title}.{$format['extension']}";
		}

		public function getFileFor($format) {
			if (!isset(self::$Formats[$format]))
				throw new Exception("$format is not a valid format!");

			$format = self::$Formats[$format];
			return "{$this->getDirectory()}/{$this->id}.{$format['extension']}";
		}

		public function getUrlFor($format) {
			if (!isset(self::$Formats[$format]))
				throw new Exception("$format is not a valid format!");

			$format = self::$Formats[$format];
			return URL::to('/t' . $this->id . '/dl.' . $format['extension']);
		}

		public function updateHash() {
			$this->hash = md5(Helpers::sanitizeInputForHashing($this->user->display_name) . ' - ' . Helpers::sanitizeInputForHashing($this->title));
		}

		public function updateTags() {
			$this->trackFiles()->touch();
			foreach (self::$Formats as $format => $data) {
				$this->{$data['tag_method']}($format);
			}
		}

		/** @noinspection PhpUnusedPrivateMethodInspection */
		private function updateTagsWithAtomicParsley($format) {
			$command = 'AtomicParsley "' . $this->getFileFor($format) . '" ';
			$command .= '--title ' . escapeshellarg($this->title) . ' ';
			$command .= '--artist ' . escapeshellarg($this->user->display_name) . ' ';
			$command .= '--year "' . $this->year . '" ';
			$command .= '--genre ' . escapeshellarg($this->genre != null ? $this->genre->name : '') . ' ';
			$command .= '--copyright ' . escapeshellarg('© '.$this->year.' '.$this->user->display_name).' ';
			$command .= '--comment "' . 'Downloaded from: https://pony.fm/' . '" ';
			$command .= '--encodingTool "' . 'Pony.fm' . '" ';
			$command .= '--encodedBy "' . 'Pony.fm - https://pony.fm/' . '" ';

			if ($this->album_id !== NULL) {
				$command .= '--album ' . escapeshellarg($this->album->title) . ' ';
				$command .= '--tracknum ' . $this->track_number . ' ';
			}

			if ($this->cover !== NULL) {
				$command .= '--artwork ' . $this->getCoverUrl() . ' ';
			}

			$command .= '--overWrite';

			External::execute($command);
		}

		/** @noinspection PhpUnusedPrivateMethodInspection */
		private function updateTagsWithGetId3($format) {
			require_once(app_path() . '/library/getid3/getid3/getid3.php');
			require_once(app_path() . '/library/getid3/getid3/write.php');
			$tagWriter = new getid3_writetags;

			$tagWriter->overwrite_tags = true;
			$tagWriter->tag_encoding   = 'UTF-8';
			$tagWriter->remove_other_tags = true;

			$tagWriter->tag_data = [
				'title'   				=> [$this->title],
				'artist'  				=> [$this->user->display_name],
				'year'    				=> ['' . $this->year],
				'genre'   				=> [$this->genre != null ? $this->genre->name : ''],
				'comment' 				=> ['Downloaded from: https://pony.fm/'],
				'copyright'				=> ['© ' . $this->year . ' ' . $this->user->display_name],
				'publisher'  			=> ['Pony.fm - https://pony.fm/'],
				'encoded_by' 			=> ['https://pony.fm/'],
//				'url_artist'			=> [$this->user->url],
//				'url_source'			=> [$this->url],
//				'url_file'				=> [$this->url],
				'url_publisher'			=> ['https://pony.fm/']
			];

			if ($this->album_id !== NULL) {
				$tagWriter->tag_data['album']	= [$this->album->title];
				$tagWriter->tag_data['track']	= [$this->track_number];
			}

			if ($format == 'MP3' && $this->cover_id != NULL && is_file($this->cover->file)) {
				$tagWriter->tag_data['attached_picture'][0] = [
					'data'			=>	file_get_contents($this->cover->file),
					'picturetypeid'	=>	2,
					'description'	=>	'cover',
					'mime'			=>	'image/png'
				];
			}

			$tagWriter->filename = $this->getFileFor($format);
			$tagWriter->tagformats = [self::$Formats[$format]['tag_format']];

			if ($tagWriter->WriteTags()) {
				if (!empty($tagWriter->warnings)) {
					Log::warning('There were some warnings:<br />' . implode('<br /><br />', $tagWriter->warnings));
				}
			} else {
				Log::error('Failed to write tags!<br />' . implode('<br /><br />', $tagWriter->errors));
			}
		}

		private function getCacheKey($key) {
			return 'track-' . $this->id . '-' . $key;
		}
	}
