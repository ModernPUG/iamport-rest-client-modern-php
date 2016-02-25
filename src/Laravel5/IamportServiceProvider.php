<?php

namespace ModernPUG\Iamport\Laravel5;

use Illuminate\Support\ServiceProvider;
use ModernPUG\Iamport\IamportApi;

class IamportServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/resources/config/iamport.php' => config_path('iamport.php'),
        ]);
    }

    public function register()
    {
        $this->app->singleton(IamportApi::class, function ($app) {
            $key = config('iamport.rest-client.key');
            $secret = config('iamport.rest-client.secret');
            return new IamportApi($key, $secret);
        });
    }
}
