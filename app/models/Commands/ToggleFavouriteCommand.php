<?php

	namespace Commands;

	use Entities\Album;
	use Entities\Favourite;
	use Entities\Playlist;
	use Entities\Track;
	use Illuminate\Support\Facades\Auth;

	class ToggleFavouriteCommand extends CommandBase {
		private $_resourceType;
		private $_resourceId;

		function __construct($resourceType, $resourceId) {
			$this->_resourceId = $resourceId;
			$this->_resourceType = $resourceType;
		}

		/**
		 * @return bool
		 */
		public function authorize() {
			$user = Auth::user();
			return$user != null;
		}

		/**
		 * @throws \Exception
		 * @return CommandResponse
		 */
		public function execute() {
			$typeId = $this->_resourceType . '_id';
			$existing = Favourite::where($typeId, '=', $this->_resourceId)->first();
			$isFavourited = false;

			if ($existing) {
				$existing->delete();
			} else {
				$fav = new Favourite();
				$fav->$typeId = $this->_resourceId;
				$fav->user_id = Auth::user()->id;
				$fav->save();
				$isFavourited = true;
			}

			return CommandResponse::succeed(['is_favourited' => $isFavourited]);
		}
	}