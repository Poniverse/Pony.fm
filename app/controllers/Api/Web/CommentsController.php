<?php

	namespace Api\Web;

	use Commands\CreateCommentCommand;
	use Entities\Album;
	use Entities\Comment;
	use Entities\Image;
	use Entities\Playlist;
	use Entities\Track;
	use Illuminate\Support\Facades\Input;
	use Illuminate\Support\Facades\Response;

	class CommentsController extends \ApiControllerBase {
		public function postCreate($type, $id) {
			return $this->execute(new CreateCommentCommand($type, $id, Input::all()));
		}

		public function getIndex($type, $id) {
			$column = '';

			if ($type == 'track')
				$column = 'track_id';
			else if ($type == 'user')
				$column = 'profile_id';
			else if ($type == 'album')
				$column = 'album_id';
			else if ($type == 'playlist')
				$column = 'playlist_id';
			else
				App::abort(500);

			$query = Comment::where($column, '=', $id)->orderBy('created_at', 'desc')->with('user');
			$comments = [];

			foreach ($query->get() as $comment) {
				$comments[] = Comment::mapPublic($comment);
			}

			return Response::json(['list' => $comments, 'count' => count($comments)]);
		}
	}