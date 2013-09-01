<?php

	use Entities\ResourceLogItem;
	use Entities\Track;
	use Illuminate\Support\Facades\App;

	class TracksController extends Controller {
		public function getIndex() {
			return View::make('tracks.index');
		}

		public function getEmbed($id) {
			$track = Track
				::whereId($id)
				->published()
				->userDetails()
				->with(
					'user',
					'user.avatar',
					'genre'
				)->first();

			if (!$track || !$track->canView(Auth::user()))
				App::abort(404);

			$userData = [
				'stats' => [
					'views' => 0,
					'plays' => 0,
					'downloads' => 0
				],
				'is_favourited' => false
			];

			if ($track->users->count()) {
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

			return View::make('tracks.embed', ['track' => $track, 'user' => $userData]);
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
			ResourceLogItem::logItem('track', $id, ResourceLogItem::PLAY, $format['index']);

			$response = Response::make('', 200);

			if (Config::get('app.sendfile')) {
				$response->header('X-Sendfile', $track->getFileFor('MP3'));
			} else {
				$response->header('X-Accel-Redirect', $track->getFileFor('MP3'));
			}

			$response->header('Content-Disposition', 'filename="' . $track->getFilenameFor('MP3') . '"');
			$response->header('Content-Type', $format['mime_type']);

			return $response;
		}

		public function getDownload($id, $extension) {
			$track = Track::find($id);
			if (!$track || !$track->canView(Auth::user()))
				App::abort(404);

			$format = null;
			$formatName = null;

			foreach (Track::$Formats as $name => $item) {
				if ($item['extension'] == $extension) {
					$format = $item;
					$formatName = $name;
					break;
				}
			}

			if ($format == null)
				App::abort(404);

			ResourceLogItem::logItem('track', $id, ResourceLogItem::DOWNLOAD, $format['index']);

			$response = Response::make('', 200);

			if (Config::get('app.sendfile')) {
				$response->header('X-Sendfile', $track->getFileFor('MP3'));
				$response->header('Content-Disposition', 'attachment; filename="' . $track->getDownloadFilenameFor($formatName) . '"');
			} else {
				$response->header('X-Accel-Redirect', $track->getFileFor('MP3'));
				$response->header('Content-Disposition', 'attachment; filename=' . $track->getDownloadFilenameFor($formatName));
			}

			$response->header('Content-Type', $format['mime_type']);

			return $response;
		}
	}