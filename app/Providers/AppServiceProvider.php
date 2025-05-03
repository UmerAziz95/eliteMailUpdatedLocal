<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        // Configure Chargebee environment
        Environment::configure(
            config('services.chargebee.site'),
            config('services.chargebee.api_key')
        );

        // Share navigations globally
        View::composer('*', function ($view) {
            if (Auth::check()) {
                $navigations = DB::table('sidebar_navigations')
                    ->orderBy('id')
                    ->get()
                    ->filter(function ($item) {
                        return Auth::user()->can($item->permission);
                    });

                $view->with('navigations', $navigations);
            }
        });
    }
}
