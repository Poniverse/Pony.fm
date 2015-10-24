<?php

namespace Poniverse\Ponyfm\Http\Middleware;

use Closure;
use Illuminate\Session\TokenMismatchException;
use Session;

class VerifyCsrfHeader
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     * @throws TokenMismatchException
     */
    public function handle($request, Closure $next)
    {
        if (Session::token() != $request->input('_token') && Session::token() != $request->header('X-Token')) {
            throw new TokenMismatchException;
        }

        return $next($request);
    }
}
