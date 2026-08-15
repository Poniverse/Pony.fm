<?php

namespace Poniverse\Lib\Errors;

class Error
{
    protected $title;

    protected $detail;

    public function __construct($title, $detail)
    {
    }

    /**
     * @return string
     */
    public function getDetail()
    {
        return $this->detail;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
}
