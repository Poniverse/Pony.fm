<?php

	namespace Entities;

	use Illuminate\Auth\UserInterface;
	use Illuminate\Auth\Reminders\RemindableInterface;

	class User extends \Eloquent implements UserInterface, RemindableInterface {
		protected $table = 'users';
		protected $hidden = ['password_hash', 'password_salt', 'bio'];

		public function getAuthIdentifier() {
			return $this->getKey();
		}

		public function getAuthPassword() {
			return $this->password_hash;
		}

		public function getReminderEmail() {
			return $this->email;
		}
	}