<?php

namespace Poniverse\Ponyfm\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * @var array
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \Poniverse\Ponyfm\Http\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Poniverse\Ponyfm\Http\Middleware\VerifyCsrfToken::class,
        \Poniverse\Ponyfm\Http\Middleware\Profiler::class,
    ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \Poniverse\Ponyfm\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'guest' => \Poniverse\Ponyfm\Http\Middleware\RedirectIfAuthenticated::class,
        'csrf' => \Poniverse\Ponyfm\Http\Middleware\VerifyCsrfHeader::class,
    ];
}
