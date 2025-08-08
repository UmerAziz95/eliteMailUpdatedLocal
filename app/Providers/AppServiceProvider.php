<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use ChargeBee\ChargeBee\Environment;
use App\Models\Order;
use App\Models\Panel;
use App\Models\User;
use App\Models\DomainRemovalTask;
use App\Models\Invoice;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use App\Observers\OrderObserver;
use App\Observers\PanelObserver;
use App\Observers\UserObserver;
use App\Observers\DomainRemovalTaskObserver;
use App\Observers\InvoiceObserver;
use App\Observers\SupportTicketObserver;
use App\Observers\TicketReplyObserver;

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
        // Register model observers
        Order::observe(OrderObserver::class);
        Panel::observe(PanelObserver::class);
        User::observe(UserObserver::class);
        DomainRemovalTask::observe(DomainRemovalTaskObserver::class);
        Invoice::observe(InvoiceObserver::class);
        SupportTicket::observe(SupportTicketObserver::class);
        TicketReply::observe(TicketReplyObserver::class);
        
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
