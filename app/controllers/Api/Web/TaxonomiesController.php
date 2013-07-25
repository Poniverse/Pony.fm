<?php

	namespace Api\Web;

	use Entities\Genre;
	use Entities\License;
	use Entities\TrackType;

	class TaxonomiesController extends \ApiControllerBase {
		public function getAll() {
			return \Response::json([
				'licenses' => License::all()->toArray(),
				'genres' => Genre::orderBy('name')->get()->toArray(),
				'track_types' => TrackType::all()->toArray()
			], 200);
		}
	}