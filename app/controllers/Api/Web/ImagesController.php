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

	class ImagesController extends \ApiControllerBase {
		public function getOwned() {
			$query = Image::where('uploaded_by', \Auth::user()->id);
			$images = [];
			foreach ($query->get() as $image) {
				$images[] = [
					'id' => $image->id,
					'url' => $image->getUrl(Image::SMALL),
					'filename' => $image->filename
				];
			}

			return Response::json($images, 200);
		}
	}