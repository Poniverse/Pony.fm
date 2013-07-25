<?php

	class TracksController extends Controller {
		public function getIndex() {
			return View::make('tracks.index');
		}
	}