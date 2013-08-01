<?php

	use Entities\Track;
	use Illuminate\Support\Facades\App;

	class TracksController extends Controller {
		public function getIndex() {
			return View::make('tracks.index');
		}

		public function getTrack($id, $slug) {
			$track = Track::find($id);
			if (!$track || !$track->canView(Auth::user()))
				App::abort(404);

			if ($track->slug != $slug)
				return Redirect::action('TracksController@getTrack', [$id, $track->slug]);

			return View::make('tracks.show');
		}

		public function getShortlink($id) {
			$track = Track::find($id);
			if (!$track || !$track->canView(Auth::user()))
				App::abort(404);

			return Redirect::action('TracksController@getTrack', [$id, $track->slug]);
		}

		public function getStream($id) {
			$track = Track::find($id);
			if (!$track || !$track->canView(Auth::user()))
				App::abort(404);

			$format = Track::$Formats['MP3'];

			$response = Response::make('', 200);
			$response->header('X-Sendfile', $track->getFileFor('MP3'));
			$response->header('Content-Disposition', 'filename=' . $track->getFilenameFor('MP3'));
			$response->header('Content-Type', $format['mime_type']);

			return $response;
		}
	}