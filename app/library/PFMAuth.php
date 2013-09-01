<?php
	use Illuminate\Auth\EloquentUserProvider;
	use Illuminate\Auth\UserInterface;
	use Illuminate\Hashing\HasherInterface;

	class NullHasher implements HasherInterface {
		public function make($value, array $options = array()) {
		}

		public function check($value, $hashedValue, array $options = array()) {
		}

		public function needsRehash($hashedValue, array $options = array()) {
		}
	}

	class PFMAuth extends EloquentUserProvider {
		function __construct() {
			parent::__construct(new NullHasher(), 'Entities\User');
		}
	}