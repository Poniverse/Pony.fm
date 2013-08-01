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
			$query = Track::summary()->with(['genre', 'user', 'cover'])->details()->whereNotNull('published_at')->orderBy('published_at', 'desc')->take(30);
			if (!Auth::check() || !Auth::user()->can_see_explicit_content)
				$query->whereIsExplicit(false);

			$tracks = [];

			foreach ($query->get() as $track) {
				$tracks[] = Track::mapPublicTrackSummary($track);
			}

			return Response::json([
				'recent_tracks' => $tracks,
				'popular_tracks' => $tracks], 200);
		}
	}