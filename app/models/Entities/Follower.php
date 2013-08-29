<?php

	namespace Entities;

	class Follower extends \Eloquent {
		protected $table = 'followers';

		public $timestamps = false;
	}