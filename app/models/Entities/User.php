<?php

	namespace Entities;

	use Cover;
	use Gravatar;
	use Illuminate\Auth\UserInterface;
	use Illuminate\Auth\Reminders\RemindableInterface;
	use Illuminate\Support\Facades\URL;
	use Illuminate\Support\Str;
	use Ratchet\Wamp\Exception;

	class User extends \Eloquent implements UserInterface, RemindableInterface {
		protected $table = 'users';
		protected $hidden = ['password_hash', 'password_salt', 'bio'];

		public function avatar() {
			return $this->belongsTo('Entities\Image');
		}

		public function comments(){
			return $this->hasMany('Entities\Comment', 'profile_id');
		}

		public function getUrlAttribute() {
			return URL::to('/' . $this->slug);
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

		public function setDisplayName($value) {
			$this->attributes['display_name'] = $value;
			$this->attributes['slug'] = Str::slug($value);
		}

		public function getAvatarUrl($type = Image::NORMAL) {
			if (!$this->uses_gravatar)
				return $this->avatar->getUrl($type);

			$email = $this->gravatar;
			if (!strlen($email))
				$email = $this->email;

			return Gravatar::getUrl($email, Image::$ImageTypes[$type]['width']);
		}

		public function getAvatarFile($type = Image::NORMAL) {
			if ($this->uses_gravatar)
				throw new Exception('Cannot get avatar file if this user is configured to use Gravatar!');

			$imageType = Image::$ImageTypes[$type];
			return URL::to('t' . $this->id . '/cover_' . $imageType['name'] . '.png?' . $this->cover_id);
		}
	}