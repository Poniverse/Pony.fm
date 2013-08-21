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
	use Entities\ResourceLogItem;
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
			$playlist = Playlist::with(['tracks.user', 'tracks.genre', 'tracks.cover', 'tracks.album', 'tracks' => function($query) { $query->details(); }, 'comments', 'comments.user'])->details()->find($id);
			if (!$playlist || !$playlist->canView(Auth::user()))
				App::abort('404');

			if (Input::get('log')) {
				ResourceLogItem::logItem('playlist', $id, ResourceLogItem::VIEW);
				$playlist->view_count++;
			}

			return Response::json(Playlist::mapPublicPlaylistShow($playlist), 200);
		}

		public function getPinned() {
			$query = Playlist
				::with(['tracks.user', 'tracks' => function($query) {}, 'comments', 'comments.user'])
				->details()
				->join('pinned_playlists', function($join) {
					$join->on('playlist_id', '=', 'playlists.id');
				})
				->where('pinned_playlists.user_id', '=', Auth::user()->id)
				->orderBy('title', 'asc')
				->select('playlists.*')
				->get();

			$playlists = [];
			foreach ($query as $playlist) {
				$mapped = Playlist::mapPublicPlaylistSummary($playlist);
				$mapped['description'] = $playlist->description;
				$mapped['is_pinned'] = true;
				$playlists[] = $mapped;
			}

			return Response::json($playlists, 200);
		}

		public function getOwned() {
			$query = Playlist::summary()->with('pins', 'tracks', 'tracks.cover')->where('user_id', \Auth::user()->id)->orderBy('title', 'asc')->get();
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