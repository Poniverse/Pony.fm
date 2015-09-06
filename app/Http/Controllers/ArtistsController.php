<?php

namespace App\Http\Controllers;

use App;
use App\User;
use Illuminate\Contracts\View\View;

class ArtistsController extends Controller
{
    public function getIndex()
    {
        return View::make('artists.index');
    }

    public function getProfile($slug)
    {
        $user = User::whereSlug($slug)->first();
        if (!$user) {
            App::abort('404');
        }

        return View::make('artists.profile');
    }

    public function getShortlink($id)
    {
        $user = User::find($id);
        if (!$user) {
            App::abort('404');
        }

        return Redirect::action('ArtistsController@getProfile', [$id]);
    }
}