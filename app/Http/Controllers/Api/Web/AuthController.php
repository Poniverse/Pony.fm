<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    public function postLogout()
    {
        \Auth::logout();
    }
}