<?php

namespace n1k3\WordpressApi;

use Illuminate\Support\ServiceProvider;

class WordpressApiServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/wordpressapi.php' => config_path('wordpress-api.php'),
        ], 'config');
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('n1k3\WordpressApi\WordpressApi',function($app){
            return new WordpressApi($app);
        });
    }
}