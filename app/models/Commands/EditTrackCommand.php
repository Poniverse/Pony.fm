<?php

	namespace Commands;

	use Entities\Image;
	use Entities\Track;
	use External;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\Log;

	class EditTrackCommand extends CommandBase {
		private $_trackId;
		private $_track;
		private $_input;

		function __construct($trackId, $input) {
			$this->_trackId = $trackId;
			$this->_track = Track::find($trackId);
			$this->_input = $input;
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
			$isVocal = isset($this->_input['is_vocal']) && $this->_input['is_vocal'] == 'true' ? true : false;

			$validator = \Validator::make($this->_input, [
				'title'			=>	'required|min:3|max:80',
				'released_at'	=>	'before:today' . ($this->_input['released_at'] != "" ? '|date' : ''),
				'lyrics'		=>	$isVocal ? 'required' : '',
				'license_id'	=>	'required|exists:licenses,id',
				'genre_id'		=>	'required|exists:genres,id',
				'cover'			=>	'image|mimes:png|min_width:350|min_height:350',
				'track_type_id'	=>	'required|exists:track_types,id',
				'songs'			=>	'required_when:track_type,2|exists:songs,id',
				'cover_id'		=>  'exists:images,id'
			]);

			if ($validator->fails())
				return CommandResponse::fail($validator);

			$track = $this->_track;
			$track->title = $this->_input['title'];
			$track->released_at = $this->_input['released_at'] != "" ? strtotime($this->_input['released_at']) : null;
			$track->description = $this->_input['description'];
			$track->lyrics = $this->_input['lyrics'];
			$track->license_id = $this->_input['license_id'];
			$track->genre_id = $this->_input['genre_id'];
			$track->track_type_id = $this->_input['track_type_id'];
			$track->is_explicit = $this->_input['is_explicit'] == 'true';
			$track->is_downloadable = $this->_input['is_downloadable'] == 'true';
			$track->is_vocal = $isVocal;

			if ($track->published_at == null) {
				$track->published_at = new \DateTime();
			}

			if (isset($this->_input['cover_id'])) {
				$track->cover_id = $this->_input['cover_id'];
			}
			else if (isset($this->_input['cover'])) {
				$cover = $this->_input['cover'];
				$track->cover_id = Image::upload($cover, Auth::user())->id;
			} else if ($this->_input['remove_cover'] == 'true')
				$track->cover_id = null;

			$track->save();

			return CommandResponse::succeed();
		}
	}