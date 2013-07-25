<?php

	class PlaylistsController extends Controller {
		public function getIndex() {
			return View::make('playlists.index');
		}
	}