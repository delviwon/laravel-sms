<?php

namespace Lewee\Sms;

use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    protected $defer = true;

    public function boot()
    {
        $this->publishes([
            __DIR__. '/../config/sms.php' => config_path('sms.php'),
        ]);
    }

    public function register()
    {
        $this->app->singleton('sms', function ($app) {
            return new Sms($app['session'], $app['config']);
        });
    }

    public function provides()
    {
        return ['sms'];
    }
}
