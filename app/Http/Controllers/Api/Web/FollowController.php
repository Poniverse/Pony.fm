<?php

namespace Poniverse\Ponyfm\Http\Controllers\Api\Web;

use Poniverse\Ponyfm\Commands\ToggleFollowingCommand;
use Poniverse\Ponyfm\Http\Controllers\ApiControllerBase;
use Illuminate\Support\Facades\Input;

class FollowController extends ApiControllerBase
{
    public function postToggle()
    {
        return $this->execute(new ToggleFollowingCommand(Input::get('type'), Input::get('id')));
    }
}