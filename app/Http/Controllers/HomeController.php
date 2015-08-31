<?php

use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function getIndex()
    {
        return View::make('home.index');
    }
}