<?php

	use Entities\Playlist;
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
	}