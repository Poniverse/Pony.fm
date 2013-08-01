<?php

	use Entities\Album;

	class AlbumsController extends Controller {
		public function getIndex() {
			return View::make('albums.index');
		}

		public function getShow($id, $slug) {
			$album = Album::find($id);
			if (!$album)
				App::abort(404);

			if ($album->slug != $slug)
				return Redirect::action('AlbumsController@getAlbum', [$id, $album->slug]);

			return View::make('albums.show');
		}

		public function getShortlink($id) {
			$album = Album::find($id);
			if (!$album)
				App::abort(404);

			return Redirect::action('AlbumsController@getTrack', [$id, $album->slug]);
		}
	}