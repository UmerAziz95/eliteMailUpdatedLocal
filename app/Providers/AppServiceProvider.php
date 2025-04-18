<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use ChargeBee\ChargeBee\Environment;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Environment::configure(
            config('services.chargebee.site'),
            config('services.chargebee.api_key')
        );
    }
}
