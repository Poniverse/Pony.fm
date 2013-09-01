<?php

	class Gravatar {
		/**
		 * Returns a Gravatar URL
		 *
		 * @param string $email The email address
		 * @param string $s Size in pixels, defaults to 80px [ 1 - 2048 ]
		 * @param string $d Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
		 * @param string $r Maximum rating (inclusive) [ g | pg | r | x ]
		 * @return Gravatar URL
		 * @source http://gravatar.com/site/implement/images/php/
		 */
		public static function getUrl( $email, $s = 80, $d = 'mm', $r = 'g') {
			$url = 'https://www.gravatar.com/avatar/';
			$url .= md5( strtolower( trim( $email ) ) );
			$url .= "?s=$s&d=$d&r=$r";
			return $url;
		}
	}