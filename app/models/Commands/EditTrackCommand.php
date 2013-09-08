<?php

	namespace Commands;

	use Entities\Album;
	use Entities\Image;
	use Entities\Track;
	use Entities\User;
	use External;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Log;

	class EditTrackCommand extends CommandBase {
		private $_trackId;
		private $_track;
		private $_input;

		function __construct($trackId, $input) {
			$this->_trackId = $trackId;
			$this->_track = Track::find($trackId);
			$this->_input = $input;
		}

		/**
		 * @return bool
		 */
		public function authorize() {
			$user = \Auth::user();
			return $this->_track && $user != null && $this->_track->user_id == $user->id;
		}

		/**
		 * @throws \Exception
		 * @return CommandResponse
		 */
		public function execute() {
			$isVocal = (isset($this->_input['is_vocal']) && $this->_input['is_vocal'] == 'true') ? true : false;

			$rules = [
				'title'			=> 'required|min:3|max:80',
				'released_at'	=> 'before:' . (date('Y-m-d', time() + (86400 * 2))) . (isset($this->_input['released_at']) && $this->_input['released_at'] != "" ? '|date' : ''),
				'license_id'	=> 'required|exists:licenses,id',
				'genre_id'		=> 'required|exists:genres,id',
				'cover'			=> 'image|mimes:png|min_width:350|min_height:350',
				'track_type_id'	=> 'required|exists:track_types,id',
				'songs'			=> 'required_when:track_type,2|exists:songs,id',
				'cover_id'		=> 'exists:images,id',
				'album_id'		=> 'exists:albums,id'
			];

			if ($isVocal)
				$rules['lyrics'] = 'required';

			if (isset($this->_input['track_type_id']) && $this->_input['track_type_id'] == 2)
				$rules['show_song_ids'] = 'required|exists:show_songs,id';

			$validator = \Validator::make($this->_input, $rules);

			if ($validator->fails())
				return CommandResponse::fail($validator);

			$track = $this->_track;
			$track->title = $this->_input['title'];
			$track->released_at = isset($this->_input['released_at']) && $this->_input['released_at'] != "" ? strtotime($this->_input['released_at']) : null;
			$track->description = isset($this->_input['description']) ? $this->_input['description'] : '';
			$track->lyrics = isset($this->_input['lyrics']) ? $this->_input['lyrics'] : '';
			$track->license_id = $this->_input['license_id'];
			$track->genre_id = $this->_input['genre_id'];
			$track->track_type_id = $this->_input['track_type_id'];
			$track->is_explicit = $this->_input['is_explicit'] == 'true';
			$track->is_downloadable = $this->_input['is_downloadable'] == 'true';
			$track->is_vocal = $isVocal;

			if (isset($this->_input['album_id']) && strlen(trim($this->_input['album_id']))) {
				if ($track->album_id != null && $track->album_id != $this->_input['album_id'])
					$this->removeTrackFromAlbum($track);

				if ($track->album_id != $this->_input['album_id']) {
					$album = Album::find($this->_input['album_id']);
					$track->track_number = $album->tracks()->count() + 1;
					$track->album_id = $this->_input['album_id'];

					Album::whereId($album->id)->update([
						'track_count' => DB::raw('(SELECT COUNT(id) FROM tracks WHERE album_id = ' . $album->id . ')')
					]);
				}
			} else {
				if ($track->album_id != null) {
					$this->removeTrackFromAlbum($track);
				}

				$track->track_number = null;
				$track->album_id = null;
			}

			if ($track->track_type_id == 2) {
				$track->showSongs()->sync(explode(',', $this->_input['show_song_ids']));
			} else
				$track->showSongs()->sync([]);

			if ($track->published_at == null) {
				$track->published_at = new \DateTime();
			}

			if (isset($this->_input['cover_id'])) {
				$track->cover_id = $this->_input['cover_id'];
			}
			else if (isset($this->_input['cover'])) {
				$cover = $this->_input['cover'];
				$track->cover_id = Image::upload($cover, Auth::user())->id;
			} else if ($this->_input['remove_cover'] == 'true')
				$track->cover_id = null;

			$track->updateTags();
			$track->save();

			User::whereId($this->_track->user_id)->update([
				'track_count' => DB::raw('(SELECT COUNT(id) FROM tracks WHERE deleted_at IS NULL AND published_at IS NOT NULL AND user_id = ' . $this->_track->user_id . ')')
			]);

			return CommandResponse::succeed(['real_cover_url' => $track->getCoverUrl(Image::NORMAL)]);
		}

		private function removeTrackFromAlbum($track) {
			$album = $track->album;
			$index = 0;

			foreach ($album->tracks as $track) {
				if ($track->id == $this->_trackId)
					continue;

				$track->track_number = ++$index;
				$track->updateTags();
				$track->save();
			}

			Album::whereId($album->id)->update([
				'track_count' => DB::raw('(SELECT COUNT(id) FROM tracks WHERE album_id = ' . $album->id . ')')
			]);
		}
	}