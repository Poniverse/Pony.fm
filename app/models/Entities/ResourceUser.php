<?php

	namespace Entities;
	use Traits\SlugTrait;

	class ResourceUser extends \Eloquent {
		protected $table = 'resource_users';
		public $timestamps = false;

		public static function get($userId, $resourceType, $resourceId) {
			$resourceIdColumn = $resourceType . '_id';
			$existing = self::where($resourceIdColumn, '=', $resourceId)->where('user_id', '=', $userId)->first();
			if ($existing)
				return $existing;

			$item = new ResourceUser();
			$item->{$resourceIdColumn} = $resourceId;
			$item->user_id = $userId;
			return $item;
		}

		public static function getId($userId, $resourceType, $resourceId) {
			$item = self::get($userId, $resourceType, $resourceId);
			if ($item->exists)
				return $item->id;

			$item->save();
			return $item->id;
		}
	}