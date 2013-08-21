<?php

	namespace Commands;

	use Entities\Album;
	use Entities\Image;
	use Entities\Track;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Log;
	use Illuminate\Support\Facades\Validator;

	class EditAlbumCommand extends CommandBase {
		private $_input;
		private $_albumId;
		private $_album;

		function __construct($trackId, $input) {
			$this->_input = $input;
			$this->_albumId = $trackId;
			$this->_album = Album::find($trackId);
		}

		/**
		 * @return bool
		 */
		public function authorize() {
			$user = Auth::user();
			return $this->_album && $user != null && $this->_album->user_id == $user->id;
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

			$this->_album->title = $this->_input['title'];
			$this->_album->description = $this->_input['description'];

			if (isset($this->_input['cover_id'])) {
				$this->_album->cover_id = $this->_input['cover_id'];
			}
			else if (isset($this->_input['cover'])) {
				$cover = $this->_input['cover'];
				$this->_album->cover_id = Image::upload($cover, Auth::user())->id;
			} else if (isset($this->_input['remove_cover']) && $this->_input['remove_cover'] == 'true')
				$this->_album->cover_id = null;

			$trackIds = explode(',', $this->_input['track_ids']);
			$this->_album->syncTrackIds($trackIds);
			$this->_album->save();

			Album::whereId($this->_album->id)->update([
				'track_count' => DB::raw('(SELECT COUNT(id) FROM tracks WHERE album_id = ' . $this->_album->id . ')')
			]);

			return CommandResponse::succeed(['real_cover_url' => $this->_album->getCoverUrl(Image::NORMAL)]);
		}
	}