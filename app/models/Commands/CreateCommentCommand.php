<?php

	namespace Commands;

	use Entities\Album;
	use Entities\Comment;
	use Entities\Image;
	use Entities\Track;
	use External;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\Log;
	use Illuminate\Support\Facades\Validator;

	class CreateCommentCommand extends CommandBase {
		private $_input;
		private $_id;
		private $_type;

		function __construct($type, $id, $input) {
			$this->_input = $input;
			$this->_id = $id;
			$this->_type = $type;
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
				'content'		=> 'required',
				'track_id'		=> 'exists:tracks,id',
				'albums_id'		=> 'exists:albums,id',
				'playlist_id'	=> 'exists:playlists,id',
				'profile_id'	=> 'exists:users,id',
			];

			$validator = Validator::make($this->_input, $rules);

			if ($validator->fails())
				return CommandResponse::fail($validator);

			$comment = new Comment();
			$comment->user_id = Auth::user()->id;
			$comment->content = $this->_input['content'];

			if ($this->_type == 'track')
				$column = 'track_id';
			else if ($this->_type == 'user')
				$column = 'profile_id';
			else if ($this->_type == 'album')
				$column = 'album_id';
			else if ($this->_type == 'playlist')
				$column = 'playlist_id';
			else
				App::abort(500);

			$comment->$column = $this->_id;
			$comment->save();

			return CommandResponse::succeed(Comment::mapPublic($comment));
		}
	}