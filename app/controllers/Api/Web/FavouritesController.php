<?php

	namespace Api\Web;

	use Commands\ToggleFavouriteCommand;
	use Entities\Album;
	use Entities\Favourite;
	use Entities\Playlist;
	use Entities\Track;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\Input;
	use Illuminate\Support\Facades\Response;

	class FavouritesController extends \ApiControllerBase {
		public function postToggle() {
			return $this->execute(new ToggleFavouriteCommand(Input::get('type'), Input::get('id')));
		}

		public function getTracks() {
			$query = Favourite
				::whereUserId(Auth::user()->id)
				->whereNotNull('track_id')
				->with([
					'track' => function($query) {
						$query
							->userDetails()
							->published();
					},
					'track.user',
					'track.genre',
					'track.cover',
					'track.album',
					'track.album.user'
				]);

			$tracks = [];

			foreach ($query->get() as $fav) {
				if ($fav->track == null) // deleted track
					continue;

				$tracks[] = Track::mapPublicTrackSummary($fav->track);
			}

			return Response::json(["tracks" => $tracks], 200);
		}

		public function getAlbums() {
			$query = Favourite
				::whereUserId(Auth::user()->id)
				->whereNotNull('album_id')
				->with([
					'album' => function($query) {
						$query->userDetails();
					},
					'album.user',
					'album.user.avatar',
					'album.cover'
				]);

			$albums = [];

			foreach ($query->get() as $fav) {
				if ($fav->album == null) // deleted album
					continue;

				$albums[] = Album::mapPublicAlbumSummary($fav->album);
			}

			return Response::json(["albums" => $albums], 200);
		}

		public function getPlaylists() {
			$query = Favourite
				::whereUserId(Auth::user()->id)
				->whereNotNull('playlist_id')
				->with([
					'playlist' => function($query) {
						$query->userDetails();
					},
					'playlist.user',
					'playlist.user.avatar',
					'playlist.tracks',
					'playlist.tracks.cover'
				]);

			$playlists = [];

			foreach ($query->get() as $fav) {
				if ($fav->playlist == null) // deleted playlist
				continue;

				$playlists[] = Playlist::mapPublicPlaylistSummary($fav->playlist);
			}

			return Response::json(["playlists" => $playlists], 200);
		}
	}