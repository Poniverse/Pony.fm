<?php

namespace Poniverse\Lib;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Contracts\Console\Application;
use Illuminate\Support\ServiceProvider;
use Poniverse\Lib\Client;

class PoniverseServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->app->singleton('poniverse.api', function (Application $app) {
            $config = $app['config']->get('poniverse/api::config');

            $client = new Client(new HttpClient());

            return $client;
        });

        $this->app->bind(Client::class, 'poniverse.api');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return string[]
     */
    public function provides()
    {
        return ['poniverse.api'];
    }
}
