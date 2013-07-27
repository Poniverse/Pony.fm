<?php

	use Entities\Image;
	use Entities\Track;
	use Illuminate\Support\Facades\App;

	class ImagesController extends Controller {
		public function getImage($id, $type) {
			$coverType = Image::GetImageTypeFromName($type);

			if ($coverType == null)
				App::abort(404);

			$image = Image::find($id);
			if (!$image)
				App::abort(404);

			return File::inline($image->getFile($coverType['id']), $image->mime, $image->filename);
		}
	}