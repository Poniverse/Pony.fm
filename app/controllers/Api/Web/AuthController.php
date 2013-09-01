<?php

	namespace Api\Web;

	use Commands\RegisterUserCommand;

	class AuthController extends \Controller {
		public function postLogout() {
			\Auth::logout();
		}
	}