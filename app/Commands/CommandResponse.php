<?php

namespace App\Commands;

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