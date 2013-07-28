<?php

	namespace Api\Web;

	use Commands\CreateAlbumCommand;
	use Commands\CreatePlaylistCommand;
	use Commands\DeleteAlbumCommand;
	use Commands\DeletePlaylistCommand;
	use Commands\DeleteTrackCommand;
	use Commands\EditAlbumCommand;
	use Commands\EditPlaylistCommand;
	use Commands\EditTrackCommand;
	use Cover;
	use Entities\Album;
	use Entities\Image;
	use Entities\PinnedPlaylist;
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

		public function getShow($id) {
			$playlist = Playlist::find($id);
			if (!$playlist || !$playlist->canView(Auth::user()))
				App::abort('404');

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
				'is_public' => $playlist->is_public == 1
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