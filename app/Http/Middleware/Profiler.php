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

use App;
use Closure;
use Cache;
use Config;
use DB;
use Log;
use Poniverse\Ponyfm\ProfileRequest;
use Symfony\Component\HttpFoundation\Response;

class Profiler
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Profiling magic time!
        if (Config::get('app.debug')) {
            DB::enableQueryLog();
            $profiler = ProfileRequest::create();

            try {
                $response = $next($request);
            } catch (\Exception $e) {
                $response = \Response::make([
                    'message' => $e->getMessage(),
                    'lineNumber' => $e->getLine(),
                    'exception' => $e->getTrace()
                ], method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500);
                $profiler->log('error', $e->__toString(), []);
            }

            $response = $this->processResponse($profiler, $response);

            Log::listen(function ($level, $message, $context) use ($profiler, $request) {
                $profiler->log($level, $message, $context);
            });

        } else {
            // Process the request the usual, boring way.
            $response = $next($request);
        }

        return $response;
    }


    protected function processResponse(ProfileRequest $profiler, Response $response) {
        $profiler->recordQueries();

        Cache::put('profiler-request-' . $profiler->getId(), $profiler->toString(), 2);
        return $response->header('X-Request-Id', $profiler->getId());
    }
}
