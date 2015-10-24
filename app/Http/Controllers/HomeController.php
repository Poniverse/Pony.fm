<?php

namespace Poniverse\Ponyfm\Http\Controllers;

use View;

class HomeController extends Controller
{
    public function getIndex()
    {
        return View::make('home.index');
    }
}