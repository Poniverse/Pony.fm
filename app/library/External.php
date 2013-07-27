<?php

	use Illuminate\Support\Facades\Log;

	class External {
		public static function execute($command) {
			$output = [];
			$error = exec($command, $output);

			if ($error != null) {
				Log::error('"' . $command . '" failed with "' . $error . '"');
			}
		}
	}