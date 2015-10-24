<?php

namespace Poniverse\Ponyfm\Providers;

use App;
use Auth;
use Illuminate\Auth\Guard;
use Illuminate\Support\ServiceProvider;
// use PFMAuth;
use PfmValidator;
use Validator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
/*
        Auth::extend('pfm', function() {
            return new Guard(new PFMAuth(), App::make('session.store'));
        });
*/

        Validator::resolver(function($translator, $data, $rules, $messages)
        {
            return new PfmValidator($translator, $data, $rules, $messages);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
