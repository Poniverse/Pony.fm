<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2015 Peter Deltchev
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Poniverse\Ponyfm\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;

class DisabledAccountCheck
{
    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * Create a new filter instance.
     *
     * @param  Guard $auth
     */
    public function __construct(Guard $auth) {
        $this->auth = $auth;
    }



    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // TODO:    don't automatically log the user out some time after
        //          issue #29 is fixed or when disabled_at starts being used for
        //          something other than merged accounts.
        if ($this->auth->check()
            && $this->auth->user()->disabled_at !== null
            && !($request->getMethod() === 'POST' && $request->getRequestUri() == '/auth/logout')
        ) {
            $this->auth->logout();
//            return Response::view('home.account-disabled', ['username' => $this->auth->user()->username], 403);
        }

        return $next($request);
    }
}
