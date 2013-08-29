<?php

	namespace Api\Web;

	use Commands\ToggleFavouriteCommand;
	use Commands\ToggleFollowingCommand;
	use Illuminate\Support\Facades\Input;

	class FollowController extends \ApiControllerBase {
		public function postToggle() {
			return $this->execute(new ToggleFollowingCommand(Input::get('type'), Input::get('id')));
		}
	}