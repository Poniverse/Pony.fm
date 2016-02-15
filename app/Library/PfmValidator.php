<?php
use Illuminate\Support\Str;

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Peter Deltchev
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class PfmValidator extends Illuminate\Validation\Validator
{
    /**
     * Determine if a given rule implies that the attribute is required.
     *
     * @param  string $rule
     * @return bool
     */
    protected function implicit($rule)
    {
        return $rule == 'required' or $rule == 'accepted' or $rule == 'required_with' or $rule == 'required_when';
    }

    /**
     * Validate the audio format of the file.
     *
     * @param  string $attribute
     * @param  array $value
     * @param  array $parameters
     * @return bool
     */
    public function validateAudioFormat($attribute, $value, $parameters)
    {
        // attribute is the file field
        // value is the file array itself
        // parameters is a list of formats the file can be, verified via ffmpeg
        $file = AudioCache::get($value->getPathname());
        $codecString = $file->getAudioCodec();

        // PCM, ADPCM, and AAC come in several variations as far as FFmpeg
        // is concerned. They're all acceptable for Pony.fm, so we check what
        // the codec string returned by FFmpeg starts with instead of looking
        // for an exact match for these.
        if (in_array('adpcm', $parameters) && Str::startsWith($codecString, 'adpcm')) {
            return true;
        }

        if (in_array('pcm', $parameters) && Str::startsWith($codecString, 'pcm')) {
            return true;
        }

        if (in_array('aac', $parameters) && Str::startsWith($codecString, 'aac')) {
            return true;
        }

        return in_array($file->getAudioCodec(), $parameters);
    }


    /**
     * Validate the sample rate of the audio file.
     *
     * @param  string $attribute
     * @param  array $value
     * @param  array $parameters
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
     * @param  string $attribute
     * @param  array $value
     * @param  array $parameters
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
     * @param  string $attribute
     * @param  array $value
     * @param  array $parameters
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
     * @param  string $attribute
     * @param  array $value
     * @param  array $parameters
     * @return bool
     */
    public function validateMinDuration($attribute, $value, $parameters)
    {
        // attribute is the file field
        // value is the file array itself
        // parameters is an array containing one value: the minimum duration
        $file = AudioCache::get($value->getPathname());

        return $file->getDuration() >= (float)$parameters[0];
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
     * public function validate_required_when($attribute, $value, $parameters)
     * {
     * if ( Input::get($parameters[0]) === $parameters[1] && static::required($attribute, $value) ){
     * return true;
     *
     * } else {
     * return false;
     * }
     * }
     **/

    // custom required_when validator
    public function validateRequiredWhen($attribute, $value, $parameters)
    {
        if (Input::get($parameters[0]) == $parameters[1]) {
            return $this->validate_required($attribute, $value);
        }

        return true;
    }


    // custom image width validator
    public function validateMinWidth($attribute, $value, $parameters)
    {
        return getimagesize($value->getPathname())[0] >= $parameters[0];
    }

    // custom image height validator
    public function validateMinHeight($attribute, $value, $parameters)
    {
        return getimagesize($value->getPathname())[1] >= $parameters[0];
    }

    public function validateTextareaLength($attribute, $value, $parameters)
    {
        return strlen(str_replace("\r\n", "\n", $value)) <= $parameters[0];
    }
}
