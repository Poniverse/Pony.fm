<?php

	namespace Entities;

	use Cover;
	use Gravatar;
	use Illuminate\Auth\UserInterface;
	use Illuminate\Auth\Reminders\RemindableInterface;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\URL;
	use Illuminate\Support\Str;
	use Ratchet\Wamp\Exception;

	class User extends \Eloquent implements UserInterface, RemindableInterface {
		protected $table = 'users';
		protected $hidden = ['password_hash', 'password_salt', 'bio'];

		public function scopeUserDetails($query) {
			if (Auth::check()) {
				$query->with(['users' => function($query) {
					$query->whereUserId(Auth::user()->id);
				}]);
			}

			return !$query;
		}

		public function avatar() {
			return $this->belongsTo('Entities\Image');
		}

		public function users() {
			return $this->hasMany('Entities\ResourceUser', 'artist_id');
		}

		public function comments() {
			return $this->hasMany('Entities\Comment', 'profile_id')->orderBy('created_at', 'desc');
		}

		public function getUrlAttribute() {
			return URL::to('/' . $this->slug);
		}

		public function getMessageUrlAttribute() {
			return 'http://mlpforums.com/index.php?app=members&module=messaging&section=send&do=form&fromMemberID='.$this->id;
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

		public function setDisplayNameAttribute($value) {
			$this->attributes['display_name'] = $value;
			$this->attributes['slug'] = Str::slug($value);
		}

		public function getAvatarUrl($type = Image::NORMAL) {
			if (!$this->uses_gravatar)
				return $this->avatar->getUrl($type);

			if ($this->email == "redacted@example.net") {
				return Gravatar::getUrl($this->id . "", Image::$ImageTypes[$type]['width'], "identicon");
			}

			$email = $this->gravatar;

			if (!strlen($email)) {
				$email = $this->email;
			}

			return Gravatar::getUrl($email, Image::$ImageTypes[$type]['width']);
		}

		public function getAvatarFile($type = Image::NORMAL) {
			if ($this->uses_gravatar)
				throw new Exception('Cannot get avatar file if this user is configured to use Gravatar!');

			$imageType = Image::$ImageTypes[$type];
			return URL::to('t' . $this->id . '/cover_' . $imageType['name'] . '.png?' . $this->cover_id);
		}

		/**
		 * Get the token value for the "remember me" session.
		 *
		 * @return string
		 */
		public function getRememberToken() {
			return $this->remember_token;
		}

		/**
		 * Set the token value for the "remember me" session.
		 *
		 * @param  string $value
		 * @return void
		 */
		public function setRememberToken($value) {
			$this->remember_token = $value;
		}

		/**
		 * Get the column name for the "remember me" token.
		 *
		 * @return string
		 */
		public function getRememberTokenName() {
			return "remember_token";
		}
	}