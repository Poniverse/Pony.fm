<?php

	namespace Entities;
	use Traits\SlugTrait;

	class Genre extends \Eloquent {
		protected $table = 'genres';
		protected $fillable = ['name', 'slug'];
		public $timestamps = false;

		use SlugTrait;
	}
