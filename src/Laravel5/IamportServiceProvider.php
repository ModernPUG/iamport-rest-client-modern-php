<?php

namespace ModernPUG\Iamport\Laravel5;

use Illuminate\Support\ServiceProvider;

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
    }
}
