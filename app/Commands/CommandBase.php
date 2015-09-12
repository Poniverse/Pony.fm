<?php

namespace App\Commands;

abstract class CommandBase
{
    private $_listeners = array();

    public function listen($listener)
    {
        $this->_listeners[] = $listener;
    }

    protected function notify($message, $progress)
    {
        foreach ($this->_listeners as $listener) {
            $listener($message, $progress);
        }
    }

    /**
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * @return CommandResponse
     */
    public abstract function execute();
}