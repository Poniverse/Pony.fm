<?php

	class Helpers {
		public static function template($template) {
			echo file_get_contents('templates/' . $template);
		}

		public static function angular($expression) {
			return '{{' . $expression . '}}';
		}
	}