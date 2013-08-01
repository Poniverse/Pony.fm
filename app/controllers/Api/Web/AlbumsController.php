<?php

	namespace Api\Web;

	use Commands\CreateAlbumCommand;
	use Commands\DeleteAlbumCommand;
	use Commands\EditAlbumCommand;
	use Entities\Album;
	use Entities\Comment;
	use Entities\Image;
	use Entities\Track;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\Input;
	use Illuminate\Support\Facades\Response;

	class AlbumsController extends \ApiControllerBase {
		public function postCreate() {
			return $this->execute(new CreateAlbumCommand(Input::all()));
		}

		public function postEdit($id) {
			return $this->execute(new EditAlbumCommand($id, Input::all()));
		}

		public function postDelete($id) {
			return $this->execute(new DeleteAlbumCommand($id));
		}

		public function getShow($id) {
			$album = Album::with(['tracks', 'user', 'comments' => function($query) { $query->with('user'); }])->details()->find($id);
			if (!$album)
				App::abort(404);

			$tracks = [];
			foreach ($album->tracks as $track) {
				$tracks[] = Track::mapPublicTrackSummary($track);
			}

			$formats = [];
			foreach (Track::$Formats as $name => $format) {
				$formats[] = [
					'name' => $name,
					'extension' => $format['extension'],
					'url' => $album->getDownloadUrl($name)
				];
			}

			$comments = [];
			foreach ($album->comments as $comment) {
				$comments[] = Comment::mapPublic($comment);
			}

			return Response::json([
				'album' => [
					'id' => $album->id,
					'formats' => $formats,
					'track_count' => $album->tracks->count(),
					'title' => $album->title,
					'description' => $album->description,
					'slug' => $album->slug,
					'created_at' => $album->created_at,
					'covers' => [
						'small' => $album->getCoverUrl(Image::SMALL),
						'normal' => $album->getCoverUrl(Image::NORMAL)
					],
					'url' => $album->url,
					'user' => [
						'id' => $album->user->id,
						'name' => $album->user->display_name,
						'url' => $album->user->url,
					],
					'tracks' => $tracks,
					'stats' => [
						'views' => 0,
						'downloads' => 0
					],
					'comments' => ['count' => count($comments), 'list' => $comments],
					'is_favourited' => $album->favourites->count() > 0
				]
			], 200);
		}

		public function getIndex() {
			$page = 1;
			if (Input::has('page'))
				$page = Input::get('page');

			$query = Album::summary()
				->with(['tracks' => function($query) { $query->details(); }, 'user'])
				->details()
				->orderBy('created_at', 'desc')
				->whereRaw('(SELECT COUNT(id) FROM tracks WHERE tracks.album_id = albums.id) > 0');

			$count = $query->count();
			$perPage = 15;

			$query->skip(($page - 1) * $perPage)->take($perPage);
			$albums = [];

			foreach ($query->get() as $album) {
				$albums[] = Album::mapPublicAlbumSummary($album);
			}

			return Response::json(["albums" => $albums, "current_page" => $page, "total_pages" => ceil($count / $perPage)], 200);
		}

		public function getOwned() {
			$query = Album::summary()->where('user_id', \Auth::user()->id)->orderBy('created_at', 'desc')->get();
			$albums = [];
			foreach ($query as $album) {
				$albums[] = [
					'id' => $album->id,
					'title' => $album->title,
					'slug' => $album->slug,
					'created_at' => $album->created_at,
					'covers' => [
						'small' => $album->getCoverUrl(Image::SMALL),
						'normal' => $album->getCoverUrl(Image::NORMAL)
					]
				];
			}
			return Response::json($albums, 200);
		}

		public function getEdit($id) {
			$album = Album::with('tracks')->find($id);
			if (!$album)
				return $this->notFound('Album ' . $id . ' not found!');

			if ($album->user_id != Auth::user()->id)
				return $this->notAuthorized();

			$tracks = [];
			foreach ($album->tracks as $track) {
				$tracks[] = [
					'id' => $track->id,
					'title' => $track->title
				];
			}

			return Response::json([
				'id' => $album->id,
				'title' => $album->title,
				'user_id' => $album->user_id,
				'slug' => $album->slug,
				'created_at' => $album->created_at,
				'published_at' => $album->published_at,
				'description' => $album->description,
				'cover_url' => $album->hasCover() ? $album->getCoverUrl(Image::NORMAL) : null,
				'real_cover_url' => $album->getCoverUrl(Image::NORMAL),
				'tracks' => $tracks
			], 200);
		}
	}