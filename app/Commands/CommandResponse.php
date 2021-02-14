<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Feld0
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

namespace App\Commands;

use Illuminate\Validation\Validator;

class CommandResponse
{
    /**
     * @var Validator
     */
    private $_validator;
    private $_response;
    private $_didFail;
    /**
     * @var int Used for HTTP responses.
     */
    private $_statusCode;

    public static function fail($validatorOrMessages, int $statusCode = 400)
    {
        $response = new CommandResponse();
        $response->_didFail = true;
        $response->_statusCode = $statusCode;

        if (is_array($validatorOrMessages)) {
            $response->_messages = $validatorOrMessages;
            $response->_validator = null;
        } else {
            $response->_validator = $validatorOrMessages;
        }

        return $response;
    }

    public static function succeed($response = null, int $statusCode = 200)
    {
        $cmdResponse = new CommandResponse();
        $cmdResponse->_didFail = false;
        $cmdResponse->_response = $response;
        $cmdResponse->_statusCode = $statusCode;

        return $cmdResponse;
    }

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
     * @return int
     */
    public function getStatusCode():int
    {
        return $this->_statusCode;
    }

    /**
     * @return Validator
     */
    public function getValidator()
    {
        return $this->_validator;
    }

    public function getMessages()
    {
        if ($this->_validator !== null) {
            return $this->_validator->messages()->getMessages();
        } else {
            return $this->_messages;
        }
    }
}
