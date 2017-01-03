<?php

namespace ModernPUG\Iamport\Laravel5;

use Illuminate\Support\ServiceProvider;
use ModernPUG\Iamport\Configuration;
use ModernPUG\Iamport\HttpClient;
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
        $this->app->singleton(Configuration::class, function () {
            return new Configuration([
                'imp_key' => config('iamport.rest_client.key'),
                'imp_secret' => config('iamport.rest_client.secret'),
            ]);
        });
        $this->app->singleton(IamportApi::class, function ($app) {
            $httpClient = new HttpClient(
                $app[Configuration::class],
                new LaravelCache
            );
            return new IamportApi($httpClient);
        });
    }
}
