<?php

namespace Poniverse\Lib\Errors;

class ApiException extends \Exception
{
    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @var int
     */
    protected $statusCode;

    /**
     * ApiException constructor.
     *
     * @param int     $statusCode
     * @param Error[] $errors
     * @param string  $message
     */
    public function __construct($statusCode, array $errors = [], $message = '')
    {
        $this->errors = $errors;
        $this->statusCode = $statusCode;

        if ($errors && ! $message) {
            $message = $errors[''];
        }

        parent::__construct($message);
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
