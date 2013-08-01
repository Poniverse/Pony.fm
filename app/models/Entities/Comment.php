<?php

	namespace Entities;

	class Comment extends \Eloquent {
		protected $table = 'comments';
		protected $softDelete = true;

		public function user(){
			return $this->belongsTo('Entities\User');
		}

		public function track(){
			return $this->belongsTo('Entities\Track');
		}

		public function album(){
			return $this->belongsTo('Entities\Album');
		}

		public function playlist(){
			return $this->belongsTo('Entities\Playlist');
		}

		public function profile(){
			return $this->belongsTo('Entities\User', 'profile_id');
		}

		public static function mapPublic($comment) {
			return [
				'id' => $comment->id,
				'created_at' => $comment->created_at,
				'content' => $comment->content,
				'user' => [
					'name' => $comment->user->display_name,
					'id' => $comment->user->id,
					'url' => $comment->user->url,
					'avatars' => [
						'normal' => $comment->user->getAvatarUrl(Image::NORMAL),
						'thumbnail' => $comment->user->getAvatarUrl(Image::THUMBNAIL),
						'small' => $comment->user->getAvatarUrl(Image::SMALL),
					]
				]
			];
		}

		public function getResourceAttribute(){
			if($this->track_id !== NULL)
				return $this->track;

			else if($this->album_id !== NULL)
				return $this->album;

			else if($this->playlist_id !== NULL)
				return $this->playlist;

			else if($this->profile_id !== NULL)
				return $this->profile;

			else return NULL;
		}
	}