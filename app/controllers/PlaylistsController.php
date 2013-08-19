<?php

	use Entities\Playlist;
	use Entities\ResourceLogItem;
	use Entities\Track;
	use Illuminate\Support\Facades\Redirect;

	class PlaylistsController extends Controller {
		public function getIndex() {
			return View::make('playlists.index');
		}

		public function getPlaylist($id, $slug) {
			$playlist = Playlist::find($id);
			if (!$playlist || !$playlist->canView(Auth::user()))
				App::abort(404);

			if ($playlist->slug != $slug)
				return Redirect::action('PlaylistsController@getPlaylist', [$id, $playlist->slug]);

			return View::make('playlists.show');
		}

		public function getShortlink($id) {
			$playlist = Playlist::find($id);
			if (!$playlist || !$playlist->canView(Auth::user()))
				App::abort(404);

			return Redirect::action('PlaylistsController@getPlaylist', [$id, $playlist->slug]);
		}

		public function getDownload($id, $extension) {
			$playlist = Playlist::with('tracks', 'user', 'tracks.album')->find($id);
			if (!$playlist || !$playlist->is_public)
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

			ResourceLogItem::logItem('playlist', $id, ResourceLogItem::DOWNLOAD, $format['index']);
			$downloader = new PlaylistDownloader($playlist, $formatName);
			$downloader->download();
		}
	}