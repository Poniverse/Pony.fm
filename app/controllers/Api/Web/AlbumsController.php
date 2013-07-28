<?php

	namespace Api\Web;

	use Commands\DeleteTrackCommand;
	use Commands\EditTrackCommand;
	use Commands\UploadTrackCommand;
	use Cover;
	use Entities\Album;
	use Entities\Image;
	use Entities\Track;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\Input;
	use Illuminate\Support\Facades\Response;

	class AlbumsController extends \ApiControllerBase {
		public function getOwned() {
			$query = Album::summary()->where('user_id', \Auth::user()->id);
			return Response::json($query->get(), 200);
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
				'show_songs' => $showSongs
			], 200);
		}

		public function postDelete($id) {
			return $this->execute(new DeleteTrackCommand($id));
		}

		public function putEdit($id) {
			return $this->execute(new EditTrackCommand($id, Input::all()));
		}
	}