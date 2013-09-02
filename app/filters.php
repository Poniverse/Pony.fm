<?php

	/*
	|--------------------------------------------------------------------------
	| Application & Route Filters
	|--------------------------------------------------------------------------
	|
	| Below you will find the "before" and "after" events for the application
	| which may be used to do any work before or after a request into your
	| application. Here you may also register your custom route filters.
	|
	*/

	use Illuminate\Support\Facades\DB;

	if (Config::get('app.debug')) {
		$profiler = \Entities\ProfileRequest::create();

		function processResponse($profiler, $request, $response) {
			$profiler->after($request, $response);

			Cache::put('profiler-request-' . $profiler->getId(), $profiler->toString(), 2);
			header('X-Request-Id: ' . $profiler->getId());
		}

		App::error(function($exception) use ($profiler) {
			$profiler->log('error', $exception->__toString(), []);
			processResponse($profiler, null, null);
		});

		App::after(function($request, $response) use ($profiler) {
			if ($response->headers->get('content-type') != 'application/json')
				return;

			processResponse($profiler, $request, $response);
		});

		Log::listen(function($level, $message, $context) use ($profiler) {
			$profiler->log($level, $message, $context);
		});

		App::error(function($exception) {
		//	return Response::view('errors.500', array(), 404);
		});
	}

	App::missing(function($exception) {
		return Response::view('errors.404', array(), 404);
	});


	/*
	|--------------------------------------------------------------------------
	| Authentication Filters
	|--------------------------------------------------------------------------
	|
	| The following filters are used to verify that the user of the current
	| session is logged into this application. The "basic" filter easily
	| integrates HTTP Basic authentication for quick, simple checking.
	|
	*/

	Route::filter('auth', function()
	{
		if (Auth::guest()) return Redirect::guest('login');
	});


	Route::filter('auth.basic', function()
	{
		return Auth::basic();
	});

	/*
	|--------------------------------------------------------------------------
	| Guest Filter
	|--------------------------------------------------------------------------
	|
	| The "guest" filter is the counterpart of the authentication filters as
	| it simply checks that the current user is not logged in. A redirect
	| response will be issued if they are, which you may freely change.
	|
	*/

	Route::filter('guest', function()
	{
		if (Auth::check()) return Redirect::to('/');
	});

	/*
	|--------------------------------------------------------------------------
	| CSRF Protection Filter
	|--------------------------------------------------------------------------
	|
	| The CSRF filter is responsible for protecting your application against
	| cross-site request forgery attacks. If this special token in a user
	| session does not match the one given in this request, we'll bail.
	|
	*/

	Route::filter('csrf', function()
	{
		if (Session::token() != Input::get('_token') && Session::token() != Request::header('X-Token')) {
			throw new Illuminate\Session\TokenMismatchException;
		}
	});