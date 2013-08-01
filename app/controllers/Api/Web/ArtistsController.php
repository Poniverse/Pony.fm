<?php

	namespace Api\Web;

	use Commands\CreateAlbumCommand;
	use Commands\DeleteAlbumCommand;
	use Commands\DeleteTrackCommand;
	use Commands\EditAlbumCommand;
	use Commands\EditTrackCommand;
	use Cover;
	use Entities\Album;
	use Entities\Image;
	use Entities\Track;
	use Entities\User;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\Input;
	use Illuminate\Support\Facades\Response;

	class ArtistsController extends \ApiControllerBase {
		public function getContent($slug) {
			$user = User::whereSlug($slug)->first();
			if (!$user)
				App::abort(404);

			$query = Track::summary()->whereUserId($user->id)->whereNotNull('published_at');
			$tracks = [];
			$singles = [];

			foreach ($query->get() as $track) {
				if ($track->album_id != null)
					$tracks[] = Track::mapPublicTrackSummary($track);
				else
					$singles[] = Track::mapPublicTrackSummary($track);
			}

			$query = Album::summary()
				->with('tracks', 'user')
				->orderBy('created_at', 'desc')
				->whereRaw('(SELECT COUNT(id) FROM tracks WHERE tracks.album_id = albums.id) > 0')
				->whereUserId($user->id);

			$albums = [];

			foreach ($query->get() as $album) {
				$albums[] = Album::mapPublicAlbumSummary($album);
			}

			return Response::json(['singles' => $singles, 'albumTracks' => $tracks, 'albums' => $albums], 200);
		}

		public function getShow($slug) {
			$user = User::whereSlug($slug)->first();
			if (!$user)
				App::abort(404);

			$trackQuery = Track::summary()->whereUserId($user->id)->whereNotNull('published_at')->orderBy('created_at', 'desc')->take(10);
			$latestTracks = [];

			foreach ($trackQuery->get() as $track) {
				$latestTracks[] = Track::mapPublicTrackSummary($track);
			}

			return Response::json([
				'artist' => [
					'id' => $user->id,
					'name' => $user->display_name,
					'slug' => $user->slug,
					'avatars' => [
						'small' => $user->getAvatarUrl(Image::SMALL),
						'normal' => $user->getAvatarUrl(Image::NORMAL)
					],
					'created_at' => $user->created_at,
					'followers' => [],
					'following' => [],
					'latest_tracks' => $latestTracks,
					'comments' => ['count' => 0, 'list' => []],
					'bio' => $user->bio,
					'mlpforums_username' => $user->mlpforums_name
				]
			], 200);
		}

		public function getIndex() {
			$page = 1;
			if (Input::has('page'))
				$page = Input::get('page');

			$query = User::orderBy('created_at', 'desc')
				->whereRaw('(SELECT COUNT(id) FROM tracks WHERE tracks.user_id = users.id) > 0');

			$count = $query->count();
			$perPage = 15;

			$query->skip(($page - 1) * $perPage)->take($perPage);
			$users = [];

			foreach ($query->get() as $user) {
				$users[] = [
					'id' => $user->id,
					'name' => $user->display_name,
					'slug' => $user->slug,
					'url' => $user->url,
					'avatars' => [
						'small' => $user->getAvatarUrl(Image::SMALL),
						'normal' => $user->getAvatarUrl(Image::NORMAL)
					],
					'created_at' => $user->created_at
				];
			}

			return Response::json(["artists" => $users, "current_page" => $page, "total_pages" => ceil($count / $perPage)], 200);
		}
	}