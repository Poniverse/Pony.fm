<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redirect;
use View;

class AccountController extends Controller
{
    public function getIndex()
    {
        return View::make('shared.null');
    }

    public function getRegister()
    {
        return Redirect::to(Config::get('poniverse.urls')['register']);
    }
}