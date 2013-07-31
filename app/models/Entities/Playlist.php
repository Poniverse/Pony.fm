<?php

	namespace Entities;
	use Traits\SlugTrait;

	class Playlist extends \Eloquent {
		protected $table = 'playlists';
		protected $softDelete = true;

		use SlugTrait;

		public static function summary() {
			return self::select('id', 'title', 'user_id', 'slug', 'created_at', 'is_public', 'description');
		}

		public function tracks() {
			return $this->belongsToMany('Entities\Track')->orderBy('position', 'asc');
		}

		public function pins() {
			return $this->hasMany('Entities\PinnedPlaylist');
		}

		public function user() {
			return $this->belongsTo('Entities\User');
		}

		public function hasPinFor($userId) {
			foreach ($this->pins as $pin) {
				if ($pin->user_id == $userId)
					return true;
			}

			return false;
		}

		public function canView($user) {
			return $this->is_public || ($user != null && $user->id == $this->user_id);
		}

		public function getUrlAttribute() {
			return '/playlist/' . $this->id . '-' . $this->slug;
		}

		public function getCoverUrl($type = Image::NORMAL) {
			if ($this->tracks->count() == 0)
				return $this->user->getAvatarUrl($type);

			return $this->tracks[0]->getCoverUrl($type);
		}

		public function pin($userId) {
			$pin = new PinnedPlaylist();
			$pin->playlist_id = $this->id;
			$pin->user_id = $userId;
			$pin->save();
		}
	}