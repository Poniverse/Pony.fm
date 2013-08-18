<?php

	namespace Entities;

	class Favourite extends \Eloquent {
		protected $table = 'favourites';

		/*
		|--------------------------------------------------------------------------
		| Relationships
		|--------------------------------------------------------------------------
		*/

		public function user() {
			return $this->belongsTo('Entities\User');
		}

		public function track() {
			return $this->belongsTo('Entities\Track');
		}

		public function album() {
			return $this->belongsTo('Entities\Album');
		}

		public function playlist() {
			return $this->belongsTo('Entities\Playlist');
		}

		/**
		 * Return the resource associated with this favourite.
		 *
		 * @return Resource|NULL
		 */
		public function getResourceAttribute(){
			if ($this->track_id)
				return $this->track;

			else if($this->album_id)
				return $this->album;

			else if($this->playlist_id)
				return $this->playlist;

			// no resource - this should never happen under real circumstances
			else
				return NULL;
		}

		public function getTypeAttribute(){
			return get_class($this->resource);
		}
	}