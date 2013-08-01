<?php

	namespace Api\Web;

	use Commands\ToggleFavouriteCommand;
	use Illuminate\Support\Facades\Input;

	class FavouritesController extends \ApiControllerBase {
		public function postToggle() {
			return $this->execute(new ToggleFavouriteCommand(Input::get('type'), Input::get('id')));
		}
	}