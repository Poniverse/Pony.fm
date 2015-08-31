<?php

namespace Api\Web;

use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    public function postLogout()
    {
        \Auth::logout();
    }
}