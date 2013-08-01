<?php

	namespace Api\Web;

	use Commands\AddTrackToPlaylistCommand;
	use Commands\CreatePlaylistCommand;
	use Commands\DeletePlaylistCommand;
	use Commands\EditPlaylistCommand;
	use Entities\Album;
	use Entities\Comment;
	use Entities\Image;
	use Entities\Playlist;
	use Entities\Track;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\Input;
	use Illuminate\Support\Facades\Response;

	class PlaylistsController extends \ApiControllerBase {
		public function postCreate() {
			return $this->execute(new CreatePlaylistCommand(Input::all()));
		}

		public function postEdit($id) {
			return $this->execute(new EditPlaylistCommand($id, Input::all()));
		}

		public function postDelete($id) {
			return $this->execute(new DeletePlaylistCommand($id, Input::all()));
		}

		public function postAddTrack($id) {
			return $this->execute(new AddTrackToPlaylistCommand($id, Input::get('track_id')));
		}

		public function getShow($id) {
			$playlist = Playlist::with(['tracks' => function($query) { $query->details(); }, 'comments' => function($query) { $query->with('user'); }])->find($id);
			if (!$playlist || !$playlist->canView(Auth::user()))
				App::abort('404');

			$tracks = [];
			foreach ($playlist->tracks as $track) {
				$tracks[] = Track::mapPublicTrackSummary($track);
			}

			$comments = [];
			foreach ($playlist->comments as $comment) {
				$comments[] = Comment::mapPublic($comment);
			}

			return Response::json([
				'id' => $playlist->id,
				'title' => $playlist->title,
				'description' => $playlist->description,
				'slug' => $playlist->slug,
				'created_at' => $playlist->created_at,
				'url' => $playlist->url,
				'covers' => [
					'small' => $playlist->getCoverUrl(Image::SMALL),
					'normal' => $playlist->getCoverUrl(Image::NORMAL)
				],
				'is_pinned' => true,
				'is_public' => $playlist->is_public == 1,
				'tracks' => $tracks,
				'comments' => ['count' => count($comments), 'list' => $comments],
			], 200);
		}

		public function getPinned() {
			$query = Playlist::join('pinned_playlists', function($join) {
				$join->on('playlist_id', '=', 'playlists.id');
			})
				->where('pinned_playlists.user_id', '=', Auth::user()->id)
				->orderBy('title', 'asc')
				->select('playlists.id', 'playlists.title', 'playlists.slug', 'playlists.created_at', 'playlists.user_id', 'playlists.is_public', 'playlists.description')
				->get();

			$playlists = [];
			foreach ($query as $playlist) {
				$playlists[] = [
					'id' => $playlist->id,
					'title' => $playlist->title,
					'description' => $playlist->description,
					'slug' => $playlist->slug,
					'created_at' => $playlist->created_at,
					'url' => $playlist->url,
					'covers' => [
						'small' => $playlist->getCoverUrl(Image::SMALL),
						'normal' => $playlist->getCoverUrl(Image::NORMAL)
					],
					'is_pinned' => true,
					'is_public' => $playlist->is_public == 1
				];
			}
			return Response::json($playlists, 200);
		}

		public function getOwned() {
			$query = Playlist::summary()->with('pins')->where('user_id', \Auth::user()->id)->orderBy('title', 'asc')->get();
			$playlists = [];
			foreach ($query as $playlist) {
				$playlists[] = [
					'id' => $playlist->id,
					'title' => $playlist->title,
					'slug' => $playlist->slug,
					'created_at' => $playlist->created_at,
					'description' => $playlist->description,
					'url' => $playlist->url,
					'covers' => [
						'small' => $playlist->getCoverUrl(Image::SMALL),
						'normal' => $playlist->getCoverUrl(Image::NORMAL)
					],
					'is_pinned' => $playlist->hasPinFor(Auth::user()->id),
					'is_public' => $playlist->is_public == 1
				];
			}
			return Response::json($playlists, 200);
		}
	}