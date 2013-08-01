<?php

	class Helpers {
		public static function template($template) {
			echo file_get_contents('templates/' . $template);
		}

		public static function angular($expression) {
			return '{{' . $expression . '}}';
		}

		public static function formatBytes($bytes, $precision = 2) {
			if ($bytes == 0)
				return '0 MB';

			$units = array('B', 'KB', 'MB', 'GB', 'TB');

			$bytes = max($bytes, 0);
			$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
			$pow = min($pow, count($units) - 1);

			$bytes /= pow(1024, $pow);

			return round($bytes, $precision) . ' ' . $units[$pow];
		}
	}