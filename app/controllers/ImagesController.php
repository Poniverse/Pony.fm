<?php

	use Entities\Image;
	use Entities\Track;
	use Illuminate\Support\Facades\App;

	class ImagesController extends Controller {
		public function getImage($id, $type) {
			$coverType = Image::getImageTypeFromName($type);

			if ($coverType == null)
				App::abort(404);

			$image = Image::find($id);
			if (!$image)
				App::abort(404);

			$response = Response::make('', 200);
			$filename = $image->getFile($coverType['id']);

			if (Config::get('app.sendfile')) {
				$response->header('X-Sendfile', $filename);
			} else {
				$response->header('X-Accel-Redirect', $filename);
			}

			$response->header('Content-Disposition', 'filename="' . $filename . '"');
			$response->header('Content-Type', 'image/png');

			$lastModified = filemtime($filename);

			header('Last-Modified: ' . $lastModified);
			header('Cache-Control: max-age=' . (60 * 60 * 24 * 7));

			return $response;
		}
	}