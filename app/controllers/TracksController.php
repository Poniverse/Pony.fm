<?php

	use Entities\Track;
	use Illuminate\Support\Facades\App;

	class TracksController extends Controller {
		public function getIndex() {
			return View::make('tracks.index');
		}
	}