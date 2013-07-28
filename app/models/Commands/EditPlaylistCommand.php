<?php

	namespace Commands;

	use Entities\Album;
	use Entities\Image;
	use Entities\PinnedPlaylist;
	use Entities\Playlist;
	use Entities\Track;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\Log;
	use Illuminate\Support\Facades\Validator;

	class EditPlaylistCommand extends CommandBase {
		private $_input;
		private $_playlistId;
		private $_playlist;

		function __construct($playlistId, $input) {
			$this->_input = $input;
			$this->_playlistId = $playlistId;
			$this->_playlist = Playlist::find($playlistId);
		}

		/**
		 * @return bool
		 */
		public function authorize() {
			$user = Auth::user();
			return $this->_playlist && $user != null && $this->_playlist->user_id == $user->id;
		}

		/**
		 * @throws \Exception
		 * @return CommandResponse
		 */
		public function execute() {
			$rules = [
				'title'         => 'required|min:3|max:50',
				'is_public'		=> 'required',
				'is_pinned'		=> 'required'
			];

			$validator = Validator::make($this->_input, $rules);

			if ($validator->fails())
				return CommandResponse::fail($validator);

			$this->_playlist->title = $this->_input['title'];
			$this->_playlist->description = $this->_input['description'];
			$this->_playlist->is_public = $this->_input['is_public'] == 'true';

			$this->_playlist->save();

			$pin = PinnedPlaylist::whereUserId(Auth::user()->id)->wherePlaylistId($this->_playlistId)->first();
			if ($pin && $this->_input['is_pinned'] != 'true')
				$pin->delete();
			else if (!$pin && $this->_input['is_pinned'] == 'true') {
				$this->_playlist->pin(Auth::user()->id);
			}

			return CommandResponse::succeed([
				'id' => $this->_playlist->id,
				'title' => $this->_playlist->title,
				'slug' => $this->_playlist->slug,
				'created_at' => $this->_playlist->created_at,
				'description' => $this->_playlist->description,
				'url' => $this->_playlist->url,
				'is_pinned' => $this->_input['is_pinned'] == 'true',
				'is_public' => $this->_input['is_public'] == 'true']);
		}
	}