<?php

namespace App\Http\Controllers;

use View;

class HomeController extends Controller
{
    public function getIndex()
    {
        return View::make('home.index');
    }
}