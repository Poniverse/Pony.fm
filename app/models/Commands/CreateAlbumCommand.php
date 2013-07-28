<?php

	namespace Commands;

	use Entities\Album;
	use Entities\Image;
	use Entities\Track;
	use External;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\Log;
	use Illuminate\Support\Facades\Validator;

	class CreateAlbumCommand extends CommandBase {
		private $_input;

		function __construct($input) {
			$this->_input = $input;
		}

		/**
		 * @return bool
		 */
		public function authorize() {
			$user = \Auth::user();
			return $user != null;
		}

		/**
		 * @throws \Exception
		 * @return CommandResponse
		 */
		public function execute() {
			$rules = [
				'title'         => 'required|min:3|max:50',
				'cover'			=> 'image|mimes:png|min_width:350|min_height:350',
				'cover_id'		=> 'exists:images,id',
				'track_ids'		=> 'exists:tracks,id'
			];

			$validator = Validator::make($this->_input, $rules);

			if ($validator->fails())
				return CommandResponse::fail($validator);

			$album = new Album();
			$album->user_id = Auth::user()->id;
			$album->title = $this->_input['title'];
			$album->description = $this->_input['description'];

			if (isset($this->_input['cover_id'])) {
				$album->cover_id = $this->_input['cover_id'];
			}
			else if (isset($this->_input['cover'])) {
				$cover = $this->_input['cover'];
				$album->cover_id = Image::upload($cover, Auth::user())->id;
			} else if (isset($this->_input['remove_cover']) && $this->_input['remove_cover'] == 'true')
				$album->cover_id = null;

			$trackIds = explode(',', $this->_input['track_ids']);
			$album->save();
			$album->syncTrackIds($trackIds);

			return CommandResponse::succeed(['id' => $album->id]);
		}
	}