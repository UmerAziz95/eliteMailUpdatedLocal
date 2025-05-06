<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\AdminOrderEmailController;
use App\Http\Controllers\Admin\AdminInvoiceController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ContractorController as AdminContractorController;
use App\Http\Controllers\AppLogController;

use App\Http\Controllers\Customer\PlanController as CustomerPlanController;
use App\Http\Controllers\Customer\InvoiceController as CustomerInvoiceController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Admin\FeatureController;
use App\Http\Controllers\Customer\SubscriptionController as CustomerSubscriptionController;

// Contractor
use App\Http\Controllers\Contractor\OrderController as ContractorOrderController;
use App\Http\Controllers\Contractor\OrderEmailController as ContractorOrderEmailController;

// Customer
use App\Http\Controllers\Customer\OrderEmailController as CustomerOrderEmailController;
//role
use App\Http\Controllers\CustomRolePermissionController;

//cron
use App\Http\Controllers\CronController;
use App\Http\Controllers\NotificationController;
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



//logs for application
Route::get('/logs', [AppLogController::class, 'getLogs'])->name('logs.index');
Route::get('/logs/specific', [AppLogController::class, 'specificLogs'])->name('specific.logs');
Route::view('/plans', 'plans');



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
Route::get('/role/assign',[CustomRolePermissionController::class,'assign'])->name('role.assign');
Route::get('/role/addpermission',[CustomRolePermissionController::class,'addPermissionMod'])->name('role.addpermission');
//cron controller
Route::prefix('cron')->name('admin.')->controller(CronController::class)->group(function () {
    Route::get('/auto_cancel_subscription', 'cancelSusbscriptons');
});

Route::middleware(['custom_role:1,2,5'])->prefix('admin')->name('admin.')->group(function () {
    //listing routes
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
       Route::middleware('view.only')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        
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
        Route::get('features/list', [FeatureController::class, 'list'])->name('features.list');
        Route::post('features/store', [FeatureController::class, 'store'])->name('features.store');
        Route::put('features/{feature}', [FeatureController::class, 'update'])->name('features.update');
        Route::delete('features/{feature}', [FeatureController::class, 'destroy'])->name('features.destroy');
        //subscription controller
        Route::get('subscriptions',[SubscriptionController::class,'index'])->name('subs.view'); //active subscriptions listings
        Route::get('cancelled_subscriptions',[SubscriptionController::class,'cancelled_subscriptions'])->name('subs.cancelled-subscriptions'); // inactive subscriptions listings
        Route::get('subscriptions_detail',[SubscriptionController::class,'index'])->name('subs.detail.view');
        Route::get('/subscription/cancel', [SubscriptionController::class, 'subscriptionCancel'])->name('subscription.cancel');
        //customer
        Route::get('/customer', [CustomerController::class, 'customerList'])->name('customerList');
        //orders
        Route::get('/orders/{id}/view', [AdminOrderController::class, 'view'])->name('orders.view');
        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders');
        Route::get('/orders/data', [AdminOrderController::class, 'getOrders'])->name('orders.data');
        Route::post('/update-order-status', [AdminOrderController::class, 'updateOrderStatus'])->name('orders.updateOrderStatus');
        Route::get('/orders/{orderId}/emails', [AdminOrderEmailController::class, 'getEmails']);
        Route::post('/subscription/cancel-process', [AdminOrderController::class, 'subscriptionCancelProcess'])->name('order.cancel.process');

        //contractors 
        Route::get('/contractor', [AdminContractorController::class, 'index'])->name('contractorList');
        Route::post('contractor/store', [AdminContractorController::class, 'store'])->name('contractor.store');
        Route::get('contractor/{id}/edit', [AdminContractorController::class, 'edit'])->name('contractor.edit');
        Route::put('contractor/{id}', [AdminContractorController::class, 'update'])->name('contractor.update');   // Update
        Route::delete('contractor/{id}', [AdminContractorController::class, 'destroy'])->name('contractor.destroy');
        //invoices
        Route::get('/invoices/data', [AdminInvoiceController::class, 'getInvoices'])->name('invoices.data');
        Route::get('/invoices/{invoiceId}', [AdminInvoiceController::class, 'show'])->name('invoices.show');
        Route::get('/invoices/{invoiceId}/download', [AdminInvoiceController::class, 'download'])->name('invoices.download');
        Route::get('/invoices', [AdminInvoiceController::class, 'index'])->name('invoices.index');
        // roles permission 
        Route::get('/role',[CustomRolePermissionController::class,'index'])->name('role.index');
        Route::get('/role/create',[CustomRolePermissionController::class,'index'])->name('role.create');
        Route::post('/role/store',[CustomRolePermissionController::class,'store'])->name('role.store');
        Route::get('/role/edit',[CustomRolePermissionController::class,'index'])->name('role.edit');
        Route::get('/role/update',[CustomRolePermissionController::class,'index'])->name('role.update');
        Route::get('/role/destroy',[CustomRolePermissionController::class,'index'])->name('role.destroy');
       
        //payments
        Route::get('/payments',[CustomRolePermissionController::class,'assign'])->name('payments');
        //settings



    }); 

});
Route::post('admin/profile/update', [App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('admin.profile.update');
Route::post('/profile/update-image', [App\Http\Controllers\ProfileController::class, 'updateProfileImage'])->name('profile.update.image');

// Route::post('admin/profile/update', [App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('admin.profile.update');
// Route::get('customer/orders/reorder/{order_id?}', [App\Http\Controllers\Customer\OrderController::class, 'reorder'])->name('customer.orders.reorder');
// Info: Customer Access

Route::middleware(['custom_role:3'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/pricing', [CustomerPlanController::class, 'index'])->name('pricing');
    // reorder routes
    // Route::get('/orders/new-order/{id}', [App\Http\Controllers\Customer\OrderController::class, 'newOrder'])->name('orders.new.order');
    // Route::get('/orders/reorder/{order_id}', [App\Http\Controllers\Customer\OrderController::class, 'reorder'])->name('orders.reorder');
    // Route::post('/orders/reorder', [App\Http\Controllers\Customer\OrderController::class, 'store'])->name('orders.reorder.store');
    // Route::get('/orders/{id}/view', [App\Http\Controllers\Customer\OrderController::class, 'view'])->name('orders.view');
    // Route::get('/orders', [App\Http\Controllers\Customer\OrderController::class, 'index'])->name('orders');
    // Route::get('/orders/data', [App\Http\Controllers\Customer\OrderController::class, 'getOrders'])->name('orders.data');
    Route::get('/dashboard', function () {
        return view('customer.dashboard');
    })->name('dashboard');
    Route::get('/orders/new-order/{id}', [CustomerOrderController::class, 'newOrder'])->name('orders.new.order');
    Route::get('/orders/reorder/{order_id}', [CustomerOrderController::class, 'reorder'])->name('orders.reorder');
    Route::post('/orders/reorder', [CustomerOrderController::class, 'store'])->name('orders.reorder.store');
    Route::get('/orders/{id}/view', [CustomerOrderController::class, 'view'])->name('orders.view');
    // customer.order.edit
    Route::get('/orders/{id}/edit', [CustomerOrderController::class, 'edit'])->name('order.edit');
    Route::get('/dashboard', function () {
        return view('customer.dashboard');
    })->name('dashboard');
    Route::get('/orders', [CustomerOrderController::class, 'index'])->name('orders');
    Route::get('/orders/data', [CustomerOrderController::class, 'getOrders'])->name('orders.data');
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
    Route::get('/plans/{id}', [CustomerPlanController::class, 'show'])->name('plans.show');
    Route::get('/plans/{id}/details', [CustomerPlanController::class, 'getPlanDetails'])->name('plans.details');
    Route::post('/plans/{id}/subscribe', [CustomerPlanController::class, 'initiateSubscription'])->name('plans.subscribe');
    Route::post('/plans/{id}/upgrade', [CustomerPlanController::class, 'upgradePlan'])->name('plans.upgrade');
    Route::post('/subscription/cancel-current', [CustomerPlanController::class, 'cancelCurrentSubscription'])->name('subscription.current.cancel');
    Route::post('/plans/update-payment-method', [CustomerPlanController::class, 'updatePaymentMethod'])->name('plans.update-payment-method');
    Route::post('/plans/card-details', [CustomerPlanController::class, 'getCardDetails'])->name('plans.card-details');
    
    // Subscription handling routes
    Route::get('/subscription/success', [CustomerPlanController::class, 'subscriptionSuccess'])->name('subscription.success');
    Route::get('/subscription/cancel', [CustomerPlanController::class, 'subscriptionCancel'])->name('subscription.cancel');
    Route::post('/subscription/cancel-process', [CustomerPlanController::class, 'subscriptionCancelProcess'])->name('subscription.cancel.process');
    Route::get('/subscription/success', [CustomerPlanController::class, 'subscriptionSuccess'])->name('subscription.success');


    //subscriptions controller
    Route::get('subscriptions',[CustomerSubscriptionController::class,'index'])->name('subscriptions.view'); //active subscriptions listings
    Route::get('cancelled_subscriptions',[CustomerSubscriptionController::class,'cancelled_subscriptions'])->name('subs.cancelled-subscriptions'); // inactive subscriptions listings
    Route::get('subscriptions_detail',[CustomerSubscriptionController::class,'index'])->name('subs.detail.view');
    // Invoice routes
    Route::get('/invoices', [CustomerInvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/data', [CustomerInvoiceController::class, 'getInvoices'])->name('invoices.data');
    Route::get('/invoices/{invoiceId}/download', [CustomerInvoiceController::class, 'download'])->name('invoices.download');
    Route::get('/invoices/{invoiceId}', [CustomerInvoiceController::class, 'show'])->name('invoices.show');

    // Order Email routes
    Route::get('/orders/{orderId}/emails', [CustomerOrderEmailController::class, 'getEmails']);
    Route::post('/orders/emails', [CustomerOrderEmailController::class, 'store']);
    Route::delete('/orders/emails/{id}', [CustomerOrderEmailController::class, 'delete']);
});

// Info: Contractor Access
Route::middleware(['custom_role:4'])->prefix('contractor')->name('contractor.')->group(function () {
    Route::get('/orders/{id}/view', [ContractorOrderController::class, 'view'])->name('orders.view');
    
    Route::get('/orders', [ContractorOrderController::class, 'index'])->name('orders');
    Route::get('/orders/data', [ContractorOrderController::class, 'getOrders'])->name('orders.data');
    Route::post('/update-order-status', [ContractorOrderController::class, 'updateStatus'])->name('orders.update.status');
    // contractor.invoices.data
    Route::get('/invoices/data', [ContractorOrderController::class, 'getInvoices'])->name('invoices.data');
    // contractor.orders.reorder
    Route::get('/orders/reorder/{order_id}', [ContractorOrderController::class, 'reorder'])->name('orders.reorder');
    Route::post('/order/status/process', [ContractorOrderController::class, 'orderStatusProcess'])->name('order.status.process');
    // Route::post('/profile/update', [App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('profile.update');
    Route::get('/dashboard', function () {
        // return view('contractor.dashboard');
        return view('contractor.dashboard');
    })->name('dashboard');
    // Route::get('/orders', function () {
    //     return view('contractor.orders.orders');
    // })->name('orders');
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
    
    // Order Email routes
    Route::get('/orders/{orderId}/emails', [ContractorOrderEmailController::class, 'getEmails']);
    Route::post('/orders/emails', [ContractorOrderEmailController::class, 'store']);
    Route::delete('/orders/emails/{id}', [ContractorOrderEmailController::class, 'delete']);
    
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

// Route::get('/roles', function () {
//     return view('admin/roles/roles');
// });

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

Route::post('/webhook/invoice', [App\Http\Controllers\Customer\PlanController::class, 'handleInvoiceWebhook'])->name('webhook.invoice');





// For Development Purpose Only
// Delete order if plan_id is null 
Route::get('/delete-order', [App\Http\Controllers\Customer\OrderController::class, 'deleteAllOrderNullPlanID'])->name('delete.order');
// Fixed Order Status to lowercase
Route::get('/update-order-status-lower-case', [App\Http\Controllers\Customer\OrderController::class, 'updateOrderStatusToLowerCase'])->name('updateOrderStatusToLowerCase');

// Notification routes
Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');
    Route::post('/notifications/{notification}/mark-as-read', function(\App\Models\Notification $notification) {
        $notification->update(['is_read' => true]);
        return response()->json(['message' => 'success']);
    })->middleware('auth');
});