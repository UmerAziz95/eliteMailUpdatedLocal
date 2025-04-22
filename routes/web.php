<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ContractorController as AdminContractorController;
use App\Http\Controllers\AppLogController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
// test section



//logs
Route::get('/logs', [AppLogController::class, 'getLogs'])->name('logs');
Route::view('/plans', 'plans');
// test section end


// Note: Authentication Routes
Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('doLogin');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/test-admin', [AuthController::class, 'testAdmin'])->name('test.admin');
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);

// // Note: Password Reset Routes
Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
Route::post('/change-password', [AuthController::class, 'changePassword'])->name('change.password')->middleware('auth');

Route::middleware(['role:1,2,5'])->prefix('admin')->name('admin.')->group(function () {
       Route::middleware('view.only')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/profile', [AdminController::class, 'profile'])->name('profile');
        Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
        Route::get('/pricing', [PlanController::class, 'index'])->name('pricing');
        //create admin 
        Route::post('users/store', [AdminController::class, 'store'])->name('users.store');
        Route::get('/{id}/edit', [AdminController::class, 'edit'])->name('edit');
        Route::put('/{id}', [AdminController::class, 'update'])->name('update');   // Update
        Route::delete('/{id}', [AdminController::class, 'destroy'])->name('admin.users.destroy');

        // Plans routes
        Route::resource('plans', PlanController::class);
        Route::get('plans-with-features', [PlanController::class, 'getPlansWithFeatures'])->name('plans.with.features');
    
        // Features routes
        Route::get('features/list', [\App\Http\Controllers\Admin\FeatureController::class, 'list'])->name('features.list');
        Route::post('features/store', [\App\Http\Controllers\Admin\FeatureController::class, 'store'])->name('features.store');
        Route::put('features/{feature}', [\App\Http\Controllers\Admin\FeatureController::class, 'update'])->name('features.update');
        Route::delete('features/{feature}', [\App\Http\Controllers\Admin\FeatureController::class, 'destroy'])->name('features.destroy');
        //subscription controller
        Route::get('subscriptions',[SubscriptionController::class,'index'])->name('subs.view'); //active subscriptions listings
        Route::get('cancelled_subscriptions',[SubscriptionController::class,'cancelled_subscriptions'])->name('subs.cancelled-subscriptions'); // inactive subscriptions listings
        Route::get('subscriptions_detail',[SubscriptionController::class,'index'])->name('subs.detail.view');
        //customer
        Route::get('/customer', [CustomerController::class, 'customerList'])->name('customerList');
        //orders
        Route::get('/orders/{id}/view', [AdminOrderController::class, 'view'])->name('orders.view');
        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders');
        Route::get('/orders/data', [AdminOrderController::class, 'getOrders'])->name('orders.data');
        Route::post('/update-order-status', [AdminOrderController::class, 'updateOrderStatus'])->name('orders.updateOrderStatus');

        //contractors
        Route::get('/contractor', [AdminContractorController::class, 'index'])->name('contractorList');
        Route::post('contractor/store', [AdminContractorController::class, 'store'])->name('contractor.store');
        Route::get('contractor/{id}/edit', [AdminContractorController::class, 'edit'])->name('edit');
        Route::put('contractor/{id}', [AdminContractorController::class, 'update'])->name('update');   // Update
        Route::delete('contractor/{id}', [AdminContractorController::class, 'destroy'])->name('contractor.destroy');
        
    }); 

});

// Route::post('admin/profile/update', [App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('admin.profile.update');
// Route::get('customer/orders/reorder/{order_id?}', [App\Http\Controllers\Customer\OrderController::class, 'reorder'])->name('customer.orders.reorder');
// Info: Customer Access

Route::middleware(['role:3'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/pricing', [App\Http\Controllers\CustomerPlanController::class, 'index'])->name('pricing');
    // reorder routes
    Route::get('/orders/new-order/{id}', [App\Http\Controllers\Customer\OrderController::class, 'newOrder'])->name('orders.new.order');
    Route::get('/orders/reorder/{order_id}', [App\Http\Controllers\Customer\OrderController::class, 'reorder'])->name('orders.reorder');
    Route::post('/orders/reorder', [App\Http\Controllers\Customer\OrderController::class, 'store'])->name('orders.reorder.store');
    Route::get('/orders/{id}/view', [App\Http\Controllers\Customer\OrderController::class, 'view'])->name('orders.view');
    Route::get('/orders', [App\Http\Controllers\Customer\OrderController::class, 'index'])->name('orders');
    Route::get('/orders/data', [App\Http\Controllers\Customer\OrderController::class, 'getOrders'])->name('orders.data');
    Route::get('/dashboard', function () {
        return view('customer.dashboard');
    })->name('dashboard');
    Route::get('/support', function () {
        return view('customer.support.support');
    })->name('support');
    Route::get('/profile', function () {
        return view('customer.profile.profile');
    })->name('profile');
    Route::get('/settings', function () {
        return view('customer.settings.settings');
    })->name('settings');

    // Plans and pricing routes 
    Route::get('/plans/{id}', [App\Http\Controllers\CustomerPlanController::class, 'show'])->name('plans.show');
    Route::get('/plans/{id}/details', [App\Http\Controllers\CustomerPlanController::class, 'getPlanDetails'])->name('plans.details');
    Route::post('/plans/{id}/subscribe', [App\Http\Controllers\CustomerPlanController::class, 'initiateSubscription'])->name('plans.subscribe');
    Route::post('/plans/{id}/upgrade', [App\Http\Controllers\CustomerPlanController::class, 'upgradePlan'])->name('plans.upgrade');
    Route::post('/subscription/cancel', [App\Http\Controllers\CustomerPlanController::class, 'cancelCurrentSubscription'])->name('subscription.cancel.current');
    
    // Subscription handling routes
    Route::get('/subscription/success', [App\Http\Controllers\CustomerPlanController::class, 'subscriptionSuccess'])->name('subscription.success');
    Route::get('/subscription/cancel', [App\Http\Controllers\CustomerPlanController::class, 'subscriptionCancel'])->name('subscription.cancel');
    Route::get('/subscription/success', [App\Http\Controllers\Customer\PlanController::class, 'subscriptionSuccess'])->name('subscription.success');
    Route::get('/subscription/cancel', [App\Http\Controllers\Customer\PlanController::class, 'subscriptionCancel'])->name('subscription.cancel');

    // Invoice routes
    Route::get('/invoices/data', [App\Http\Controllers\Customer\InvoiceController::class, 'getInvoices'])->name('invoices.data');
});

// Info: Contractor Access
Route::middleware(['role:4'])->prefix('contractor')->name('contractor.')->group(function () {
  
    // Route::post('/profile/update', [App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('profile.update');
    Route::get('/dashboard', function () {
        // return view('contractor.dashboard');
        return view('contractor.dashboard');
    })->name('dashboard');
    Route::get('/orders', function () {
        return view('contractor.orders.orders');
    })->name('orders');
    Route::get('/pricing', function () {
        return view('contractor.pricing.pricing');
    })->name('pricing');
    Route::get('/payments', function () {
        return view('contractor.payments.payments');
    })->name('payments');
    Route::get('/support', function () {
        return view('contractor.support.support');
    })->name('support');
    Route::get('/profile', function () {
        return view('contractor.profile.profile');
    })->name('profile');
    Route::get('/settings', function () {
        return view('contractor.settings.settings');
    })->name('settings');
});

Route::get('/forget_password', function () {
    return view('admin/auth/forget_password');
});

Route::get('/reset_password', function () {
    return view('admin/auth/reset_password');
});


// Route::get('/admins', function () {
//     return view('admin/admins/admins');
// });

Route::get('/customers', function () {
    return view('admin/customers/customers');
});

Route::get('/contractor', function () {
    return view('admin/contractor/contractor');
});

Route::get('/roles', function () {
    return view('admin/roles/roles');
});

Route::get('/permissions', function () {
    return view('admin/permissions/permissions');
});

Route::get('/payments', function () {
    return view('admin/payments/payments');
});



Route::get('/orders', function () {
    return view('admin/orders/orders');
});

Route::get('/contact_us', function () {
    return view('admin/contact_us/contact_us');
});

Route::get('/support', function () {
    return view('admin/support/support');
});

Route::get('/profile', function () {
    return view('admin/profile/profile');
})->name('profile');

Route::get('/settings', function () {
    return view('admin/settings/settings');
});
    
Route::get('/notification', function () {
    return view('admin/notification/notification');
});
Route::get('/chargebee/webhook', function () {
    Log::info('Chargebee Webhook Triggered');
});

