<?php

use App\Http\Controllers\Controller;

class UploaderController extends Controller
{
    public function getIndex()
    {
        return View::make('shared.null');
    }
}