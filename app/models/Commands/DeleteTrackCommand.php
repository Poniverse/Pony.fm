<?php

	namespace Commands;

	use Entities\Track;

	class DeleteTrackCommand extends CommandBase {
		private $_trackId;
		private $_track;

		function __construct($trackId) {
			$this->_trackId = $trackId;
			$this->_track = Track::find($trackId);
		}

		/**
		 * @return bool
		 */
		public function authorize() {
			$user = \Auth::user();
			return $this->_track && $user != null && $this->_track->user_id == $user->id;
		}

		/**
		 * @throws \Exception
		 * @return CommandResponse
		 */
		public function execute() {
			$this->_track->delete();
			return CommandResponse::succeed();
		}
	}