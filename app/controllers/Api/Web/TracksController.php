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

		public function getShow($id) {
			$track = Track::find($id);
			if (!$track || !$track->canView(Auth::user()))
				return $this->notFound('Track not found!');

			return Response::json(['track' => Track::mapPublicTrackShow($track)], 200);
		}

		public function getRecent() {
			$query = Track::summary()->with(['genre', 'user', 'cover'])->whereNotNull('published_at')->orderBy('published_at', 'desc')->take(15);
			if (!Auth::check() || !Auth::user()->can_see_explicit_content)
				$query->whereIsExplicit(false);

			$tracks = [];

			foreach ($query->get() as $track) {
				$tracks[] = Track::mapPublicTrackSummary($track);
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
			foreach ($query->get() as $track)
				$tracks[] = Track::mapPublicTrackSummary($track);

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

			$tracks = [];
			foreach ($query->get() as $track)
				$tracks[] = Track::mapPrivateTrackSummary($track);

			return Response::json($tracks, 200);
		}

		public function getEdit($id) {
			$track = Track::with('showSongs')->find($id);
			if (!$track)
				return $this->notFound('Track ' . $id . ' not found!');

			if ($track->user_id != Auth::user()->id)
				return $this->notAuthorized();

			return Response::json(Track::mapPrivateTrackShow($track), 200);
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