<?php

	namespace Entities;
	use Helpers;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\Cache;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\URL;
	use Traits\SlugTrait;

	class ProfileRequest {
		private $_id = null;
		private $_data = null;

		public static function load($data) {
			$req = new ProfileRequest();
			$req->_data = json_decode($data);
			return $req;
		}

		public static function create() {
			$req = new ProfileRequest();
			$req->_id = uniqid();
			return $req;
		}

		private function __construct() {
			$this->_data = ['log' => []];
		}

		public function toArray() {
			return $this->_data;
		}

		public function toString() {
			return json_encode($this->_data);
		}

		public function getId() {
			return $this->_id;
		}

		public function after($request, $response) {
			$this->_data['queries'] = [];
			foreach (DB::getQueryLog() as $query) {
				if (starts_with($query['query'], 'select * from `cache` where'))
					continue;

				if (starts_with($query['query'], 'delete from `cache` where'))
					continue;

				if (starts_with($query['query'], 'insert into `cache`'))
					continue;

				$this->_data['queries'][] = $query;
			}
		}

		public function log($level, $message, $context) {
			$this->_data['log'][] = [
				'level' => $level,
				'message' => $message
			];
		}
	}