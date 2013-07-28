<?php

	namespace Api\Web;

	use Commands\DeleteTrackCommand;
	use Commands\EditTrackCommand;
	use Commands\UploadTrackCommand;
	use Cover;
	use Entities\Image;
	use Entities\Track;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\Input;
	use Illuminate\Support\Facades\Response;

	class DashboardController extends \ApiControllerBase {
		public function getIndex() {
			$query = Track::summary()->with(['genre', 'user', 'cover'])->whereNotNull('published_at')->orderBy('published_at', 'desc')->take(15);
			if (!Auth::check() || !Auth::user()->can_see_explicit_content)
				$query->whereIsExplicit(false);

			$tracks = [];

			foreach ($query->get() as $track) {
				$tracks[] = [
					'id' => $track->id,
					'title' => $track->title,
					'user' => [
						'id' => $track->user->id,
						'name' => $track->user->display_name,
						'url' => $track->user->url
					],
					'url' => $track->url,
					'slug' => $track->slug,
					'is_vocal' => $track->is_vocal,
					'is_explicit' => $track->is_explicit,
					'is_downloadable' => $track->is_downloadable,
					'is_published' => $track->isPublished(),
					'published_at' => $track->published_at,
					'duration' => $track->duration,
					'genre' => [
						'id' => $track->genre->id,
						'slug' => $track->genre->slug,
						'name' => $track->genre->name
					],
					'track_type_id' => $track->track_type_id,
					'covers' => [
						'thumbnail' => $track->getCoverUrl(Image::THUMBNAIL),
						'small' => $track->getCoverUrl(Image::SMALL),
						'normal' => $track->getCoverUrl(Image::NORMAL)
					]
				];
			}

			return Response::json([
				'recent_tracks' => $tracks,
				'popular_tracks' => $tracks], 200);
		}
	}