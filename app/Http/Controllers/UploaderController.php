<?php

namespace App\Http\Controllers;

use View;

class UploaderController extends Controller
{
    public function getIndex()
    {
        return View::make('shared.null');
    }
}