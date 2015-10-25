<?php

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

namespace Poniverse\Ponyfm\Commands;

use Illuminate\Validation\Validator;

class CommandResponse
{
    public static function fail($validator)
    {
        $response = new CommandResponse();
        $response->_didFail = true;
        $response->_validator = $validator;

        return $response;
    }

    public static function succeed($response = null)
    {
        $cmdResponse = new CommandResponse();
        $cmdResponse->_didFail = false;
        $cmdResponse->_response = $response;

        return $cmdResponse;
    }

    private $_validator;
    private $_response;
    private $_didFail;

    private function __construct()
    {
    }

    /**
     * @return bool
     */
    public function didFail()
    {
        return $this->_didFail;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * @return Validator
     */
    public function getValidator()
    {
        return $this->_validator;
    }
}
