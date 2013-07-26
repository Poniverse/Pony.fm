<?php

	namespace Api\Web;

	use Commands\DeleteTrackCommand;
	use Commands\EditTrackCommand;
	use Commands\UploadTrackCommand;
	use Entities\Track;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\Input;
	use Illuminate\Support\Facades\Response;

	class TracksController extends \ApiControllerBase {
		public function postUpload() {
			session_write_close();
			return $this->execute(new UploadTrackCommand());
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
					'is_published' => $track->published_at != null,
					'created_at' => $track->created_at,
					'published_at' => $track->published_at,
					'duration' => $track->duration,
					'genre_id' => $track->genre_id,
					'track_type_id' => $track->track_type_id,
				];
			}

			return Response::json($tracks, 200);
		}

		public function getEdit($id) {
			$track = Track::find($id);
			if (!$track)
				return $this->notFound('Track ' . $id . ' not found!');

			if ($track->user_id != Auth::user()->id)
				return $this->notAuthorized();

			return Response::json([
				'id' => $track->id,
				'title' => $track->title,
				'user_id' => $track->user_id,
				'slug' => $track->slug,
				'is_vocal' => (bool)$track->is_vocal,
				'is_explicit' => (bool)$track->is_explicit,
				'is_downloadable' => $track->published_at == null ? true : (bool)$track->is_downloadable,
				'is_published' => $track->published_at != null,
				'created_at' => $track->created_at,
				'published_at' => $track->published_at,
				'duration' => $track->duration,
				'genre_id' => $track->genre_id,
				'track_type_id' => $track->track_type_id,
				'license_id' => $track->license_id != null ? $track->license_id : 3,
				'description' => $track->description,
				'lyrics' => $track->lyrics,
				'released_at' => $track->released_at
			], 200);
		}

		public function postDelete($id) {
			return $this->execute(new DeleteTrackCommand($id));
		}

		public function putEdit($id) {
			return $this->execute(new EditTrackCommand($id, Input::all()));
		}
	}