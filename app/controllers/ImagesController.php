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

			$filename = $image->getFile($coverType['id']);
			$lastModified = filemtime($filename);

			if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $lastModified == $_SERVER['HTTP_IF_MODIFIED_SINCE']) {
				header('HTTP/1.0 304 Not Modified');
				exit();
			}

			header('Last-Modified: ' . $lastModified);
			header('Cache-Control: max-age=' . (60 * 60 * 24 * 7));

			return File::inline($filename, $image->mime, $image->filename);
		}
	}