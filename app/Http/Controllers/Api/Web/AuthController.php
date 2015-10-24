<?php

namespace Poniverse\Ponyfm\Http\Controllers\Api\Web;

use Poniverse\Ponyfm\Http\Controllers\Controller;

class AuthController extends Controller
{
    public function postLogout()
    {
        \Auth::logout();
    }
}