<?php

	use Entities\Track;

	class HomeController extends Controller {
		public function getIndex() {
			return View::make('home.index');
		}
	}