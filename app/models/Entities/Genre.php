<?php

	namespace Entities;
	use Traits\SlugTrait;

	class Genre extends \Eloquent {
		protected $table = 'genres';

		use SlugTrait;
	}