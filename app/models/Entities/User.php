<?php

	namespace Entities;

	use Cover;
	use Gravatar;
	use Illuminate\Auth\UserInterface;
	use Illuminate\Auth\Reminders\RemindableInterface;

	class User extends \Eloquent implements UserInterface, RemindableInterface {
		protected $table = 'users';
		protected $hidden = ['password_hash', 'password_salt', 'bio'];

		public function avatar() {
			return $this->hasOne('Entities\Image');
		}

		public function cover() {
			return $this->belongsTo('Entities\Image');
		}

		public function getAuthIdentifier() {
			return $this->getKey();
		}

		public function getAuthPassword() {
			return $this->password_hash;
		}

		public function getReminderEmail() {
			return $this->email;
		}

		public function getAvatarUrl($type = Cover::NORMAL) {
			if (!$this->uses_gravatar)
				return $this->cover->getUrl();

			$email = $this->gravatar;
			if (!strlen($email))
				$email = $this->email;

			return Gravatar::getUrl($email, Image::$ImageTypes[$type]['width']);
		}

		public function getAvatarFile($type = Cover::NORMAL) {
			if ($this->uses_gravatar)
				return $this->user->getAvatar($type);

			$cover = Cover::$Covers[$type];
			return URL::to('t' . $this->id . '/cover_' . $cover['name'] . '.png?' . $this->cover_id);
		}
	}