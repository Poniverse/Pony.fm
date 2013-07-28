<?php

	namespace Entities;
	use Traits\SlugTrait;

	class PinnedPlaylist extends \Eloquent {
		protected $table = 'pinned_playlists';

		public function user() {
			return $this->belongsTo('Entities\User');
		}

		public function playlist() {
			return $this->belongsTo('Entities\Playlist');
		}
	}