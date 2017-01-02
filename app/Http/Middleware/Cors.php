<?php

namespace Poniverse\Ponyfm\Http\Middleware;

use App;

class Cors {
    public function handle($request, $next)
    {
        if (App::environment('local', 'staging')) {
            return $next($request)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        } else {
            return $next($request);
        }
    }
}
