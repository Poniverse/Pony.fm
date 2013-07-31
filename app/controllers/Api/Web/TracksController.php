<?php

	namespace Api\Web;

	use Commands\DeleteTrackCommand;
	use Commands\EditTrackCommand;
	use Commands\UploadTrackCommand;
	use Cover;
	use Entities\Image;
	use Entities\Track;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\Input;
	use Illuminate\Support\Facades\Response;

	class TracksController extends \ApiControllerBase {
		public function postUpload() {
			session_write_close();
			return $this->execute(new UploadTrackCommand());
		}

		public function postDelete($id) {
			return $this->execute(new DeleteTrackCommand($id));
		}

		public function postEdit($id) {
			return $this->execute(new EditTrackCommand($id, Input::all()));
		}

		public function getRecent() {
			$query = Track::summary()->with(['genre', 'user', 'cover'])->whereNotNull('published_at')->orderBy('published_at', 'desc')->take(15);
			if (!Auth::check() || !Auth::user()->can_see_explicit_content)
				$query->whereIsExplicit(false);

			$tracks = [];

			foreach ($query->get() as $track) {
				$tracks[] = $this->mapPublicTrack($track);
			}

			return Response::json($tracks, 200);
		}

		public function getIndex() {
			$page = 1;

			if (Input::has('page'))
				$page = Input::get('page');

			$query = Track::summary()->whereNotNull('published_at');
			$this->applyFilters($query);

			$totalCount = $query->count();
			$query->take(30)->skip(30 * ($page - 1));
			$tracks = [];

			foreach ($query->get() as $track) {
				$tracks[] = $this->mapPublicTrack($track);
			}

			return Response::json(["tracks" => $tracks, "current_page" => $page, "total_pages" => ceil($totalCount / 30)], 200);
		}

		public function getOwned() {
			$query = Track::summary()->where('user_id', \Auth::user()->id);

			if (Input::has('published')) {
				$published = \Input::get('published');
				if ($published)
					$query->whereNotNull('published_at');
				else
					$query->whereNull('published_at');
			}

			$this->applyFilters($query);

			$dbTracks = $query->get();
			$tracks = [];

			foreach ($dbTracks as $track) {
				$tracks[] = [
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
					'cover_url' => $track->getCoverUrl(Image::SMALL)
				];
			}

			return Response::json($tracks, 200);
		}

		public function getEdit($id) {
			$track = Track::with('showSongs')->find($id);
			if (!$track)
				return $this->notFound('Track ' . $id . ' not found!');

			if ($track->user_id != Auth::user()->id)
				return $this->notAuthorized();

			$showSongs = [];
			foreach ($track->showSongs as $showSong) {
				$showSongs[] = ['id' => $showSong->id, 'title' => $showSong->title];
			}

			return Response::json([
				'id' => $track->id,
				'title' => $track->title,
				'user_id' => $track->user_id,
				'slug' => $track->slug,
				'is_vocal' => (bool)$track->is_vocal,
				'is_explicit' => (bool)$track->is_explicit,
				'is_downloadable' => !$track->isPublished() ? true : (bool)$track->is_downloadable,
				'is_published' => $track->published_at != null,
				'created_at' => $track->created_at,
				'published_at' => $track->published_at,
				'duration' => $track->duration,
				'genre_id' => $track->genre_id,
				'track_type_id' => $track->track_type_id,
				'license_id' => $track->license_id != null ? $track->license_id : 3,
				'description' => $track->description,
				'lyrics' => $track->lyrics,
				'released_at' => $track->released_at,
				'cover_url' => $track->hasCover() ? $track->getCoverUrl(Image::NORMAL) : null,
				'real_cover_url' => $track->getCoverUrl(Image::NORMAL),
				'show_songs' => $showSongs,
				'album_id' => $track->album_id
			], 200);
		}

		private function mapPublicTrack($track) {
			return [
				'id' => $track->id,
				'title' => $track->title,
				'user' => [
					'id' => $track->user->id,
					'name' => $track->user->display_name,
					'url' => $track->user->url
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
				]
			];
		}

		private function applyFilters($query) {
			if (Input::has('order')) {
				$order = \Input::get('order');
				$parts = explode(',', $order);
				$query->orderBy($parts[0], $parts[1]);
			}

			if (Input::has('is_vocal')) {
				$isVocal = \Input::get('is_vocal');
				if ($isVocal == 'true')
					$query->whereIsVocal(true);
				else
					$query->whereIsVocal(false);
			}

			if (Input::has('in_album')) {
				if (Input::get('in_album') == 'true')
					$query->whereNotNull('album_id');
				else
					$query->whereNull('album_id');
			}

			if (Input::has('genres'))
				$query->whereIn('genre_id', Input::get('genres'));

			if (Input::has('types'))
				$query->whereIn('track_type_id', Input::get('types'));

			if (Input::has('songs')) {
				$query->join('show_song_track', 'tracks.id', '=', 'show_song_track.track_id')
					->whereIn('show_song_track.show_song_id', Input::get('songs'));

				$query->select('tracks.*');
			}

			return $query;
		}
	}