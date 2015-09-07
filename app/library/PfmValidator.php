<?php // namespace extensions;
	class PfmValidator extends Illuminate\Validation\Validator {
		/**
		 * Determine if a given rule implies that the attribute is required.
		 *
		 * @param  string  $rule
		 * @return bool
		 */
		protected function implicit($rule)
		{
			return $rule == 'required' or $rule == 'accepted' or $rule == 'required_with' or $rule == 'required_when';
		}

		/**
		 * Validate the audio format of the file.
		 *
		 * @param  string  $attribute
		 * @param  array   $value
		 * @param  array   $parameters
		 * @return bool
		 */
		public function validateAudioFormat($attribute, $value, $parameters)
		{
			// attribute is the file field
			// value is the file array itself
			// parameters is a list of formats the file can be, verified via ffmpeg
			$file = AudioCache::get($value->getPathname());
			return in_array($file->getAudioCodec(), $parameters);
		}


		/**
		 * Validate the sample rate of the audio file.
		 *
		 * @param  string  $attribute
		 * @param  array   $value
		 * @param  array   $parameters
		 * @return bool
		 */
		public function validateSampleRate($attribute, $value, $parameters)
		{
			// attribute is the file field
			// value is the file array itself
			// parameters is a list of sample rates the file can be, verified via ffmpeg
			$file = AudioCache::get($value->getPathname());
			return in_array($file->getAudioSampleRate(), $parameters);
		}


		/**
		 * Validate the number of channels in the audio file.
		 *
		 * @param  string  $attribute
		 * @param  array   $value
		 * @param  array   $parameters
		 * @return bool
		 */
		public function validateAudioChannels($attribute, $value, $parameters)
		{
			// attribute is the file field
			// value is the file array itself
			// parameters is a list of sample rates the file can be, verified via ffmpeg
			$file = AudioCache::get($value->getPathname());
			return in_array($file->getAudioChannels(), $parameters);
		}


		/**
		 * Validate the bit rate of the audio file.
		 *
		 * @param  string  $attribute
		 * @param  array   $value
		 * @param  array   $parameters
		 * @return bool
		 */
		public function validateAudioBitrate($attribute, $value, $parameters)
		{
			// attribute is the file field
			// value is the file array itself
			// parameters is a list of sample rates the file can be, verified via ffmpeg
			$file = AudioCache::get($value->getPathname());
			return in_array($file->getAudioBitRate(), $parameters);
		}


		/**
		 * Validate the duration of the audio file, in seconds.
		 *
		 * @param  string  $attribute
		 * @param  array   $value
		 * @param  array   $parameters
		 * @return bool
		 */
		public function validateMinDuration($attribute, $value, $parameters)
		{
			// attribute is the file field
			// value is the file array itself
			// parameters is an array containing one value: the minimum duration
			$file = AudioCache::get($value->getPathname());
			return $file->getDuration() >= (float) $parameters[0];
		}


		/**
		 * Require a field when the value of another field matches a certain value.
		 *
		 * @param string $attribute
		 * @param array $value
		 * @param array $parameters
		 * @return bool
		 */
		/** OLD CODE
		public function validate_required_when($attribute, $value, $parameters)
		{
		if ( Input::get($parameters[0]) === $parameters[1] && static::required($attribute, $value) ){
		return true;

		} else {
		return false;
		}
		}
		 **/

		// custom required_when validator
		public function validateRequiredWhen($attribute, $value, $parameters){
			if ( Input::get($parameters[0]) == $parameters[1] ) {
				return $this->validate_required($attribute, $value);
			}

			return true;
		}


		// custom image width validator
		public function validateMinWidth($attribute, $value, $parameters){
			return getimagesize($value->getPathname())[0] >= $parameters[0];
		}

		// custom image height validator
		public function validateMinHeight($attribute, $value, $parameters){
			return getimagesize($value->getPathname())[1] >= $parameters[0];
		}

		public function validateTextareaLength($attribute, $value, $parameters) {
			return strlen(str_replace("\r\n", "\n", $value)) <= $parameters[0];
		}
	}
