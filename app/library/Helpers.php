<?php

	class Helpers {
		public static function template($template) {
			echo file_get_contents('templates/' . $template);
		}
	}