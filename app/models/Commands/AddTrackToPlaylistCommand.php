<?php

	namespace Commands;

	use Entities\Album;
	use Entities\Favourite;
	use Entities\Playlist;
	use Entities\Track;
	use Illuminate\Support\Facades\Auth;

	class AddTrackToPlaylistCommand extends CommandBase {
		private $_track;
		private $_playlist;

		function __construct($playlistId, $trackId) {
			$this->_playlist = Playlist::find($playlistId);
			$this->_track = Track::find($trackId);
		}

		/**
		 * @return bool
		 */
		public function authorize() {
			$user = Auth::user();
			return $user != null && $this->_playlist && $this->_track && $this->_playlist->user_id == $user->id;
		}

		/**
		 * @throws \Exception
		 * @return CommandResponse
		 */
		public function execute() {
			$songIndex = $this->_playlist->tracks()->count() + 1;
			$this->_playlist->tracks()->attach($this->_track, ['position' => $songIndex]);
			return CommandResponse::succeed(['message' => 'Track added!']);
		}
	}