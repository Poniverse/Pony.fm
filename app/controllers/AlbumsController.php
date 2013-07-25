<?php

	class AlbumsController extends Controller {
		public function getIndex() {
			return View::make('albums.index');
		}
	}