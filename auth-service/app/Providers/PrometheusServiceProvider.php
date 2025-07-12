<?php

namespace App\Providers;

use Prometheus\Storage\Redis;
use Prometheus\CollectorRegistry;

use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

class PrometheusServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(CollectorRegistry::class, function() {
            Redis::setDefaultOptions(
                Arr::only(
                     config('database.redis.default'), 
                     ['host', 'port', 'password']
                )
            );

            return CollectorRegistry::getDefault();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
