<?php

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
                $response = \Response::make(['exception' => $e->getTrace()], 500);
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
