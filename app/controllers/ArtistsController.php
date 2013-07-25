<?php

	class ArtistsController extends Controller {
		public function getIndex() {
			return View::make('artists.index');
		}
	}