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

		public function getOwned() {
			$query = Track::summary()->whereNull('deleted_at')->where('user_id', \Auth::user()->id);

			if (Input::has('published')) {
				$published = \Input::get('published');
				if ($published)
					$query->whereNotNull('published_at');
				else
					$query->whereNull('published_at');
			}

			if (Input::has('order')) {
				$order = \Input::get('order');
				$parts = explode(',', $order);
				$query->orderBy($parts[0], $parts[1]);
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
	}