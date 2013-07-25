<?php

	namespace Api\Web;

	use Commands\RegisterUserCommand;

	class AuthController extends \Controller {
		public function postLogin() {
			if (!\Auth::attempt(array('email' => \Input::get('email'), 'password' => \Input::get('password')), \Input::get('remember')))
				return \Response::json(['messages' => ['username' => 'Invalid username or password']], 400);

			return \Response::json(['user' => \Auth::user()]);
		}

		public function postLogout() {
			\Auth::logout();
		}

		public function postRegister() {
			$command = new RegisterUserCommand();
			if (!$command->authorize())
				return \Response::json([], 403);

			$errors = $command->validate();
			if ($errors->fails())
				return \Response::json([], 400);

			return \Response::json($command->execute());
		}
	}