<?php
use ChargeBee\ChargeBee\Environment;
use ChargeBee\ChargeBee\PaymentSource;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\AdminOrderEmailController;
use App\Http\Controllers\Admin\AdminInvoiceController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\Admin\PanelController as AdminPanelController;
use App\Http\Controllers\Contractor\PanelController as ContractorPanelController;
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
use App\Http\Controllers\Contractor\OrderQueueController as ContractorOrderQueueController;

// Customer
use App\Http\Controllers\Customer\OrderEmailController as CustomerOrderEmailController;
//role
use App\Http\Controllers\CustomRolePermissionController;
use App\Http\Controllers\Admin\MediaHandlerController;
//supports
use App\Http\Controllers\Admin\AdminSupportController;
use App\Http\Controllers\Customer\CustomerSupportController;
//settings
use App\Http\Controllers\Admin\AdminSettingsController;


//cron
use App\Http\Controllers\CronController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\DB;
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


// logs for application
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

// Note: Password Reset Routes
Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
Route::post('/change-password', [AuthController::class, 'changePassword'])->name('change.password')->middleware('auth');
Route::get('/role/assign',[CustomRolePermissionController::class,'assign'])->name('role.assign');
Route::get('/role/addpermission',[CustomRolePermissionController::class,'addPermissionMod'])->name('role.addpermission');
// verfiy email address
Route::get('/email_verification/{encrypted}', [AuthController::class, 'showVerifyEmailForm'])->name('verify_email.request');
Route::get('/resend-verfication-code/{encrypted}', [AuthController::class, 'resendVerificationEmail'])->name('resend.verification');
Route::post('/verify-email', [AuthController::class, 'VerifyEmailNow'])->name('verify.email.code');
Route::get('/onboarding/{encrypted}', [AuthController::class, 'companyOnBoarding'])->name('company.onboarding');
Route::post('/onboarding/store', [AuthController::class, 'companyOnBoardingStore'])->name('company.onboarding.store');

//public plans
Route::get('/plans/public/{encrypted}', [AuthController::class, 'viewPublicPlans'])->name('public.plnas');

// Chargebee webhooks (no auth required)
Route::post('/webhook/chargebee/master-plan', [App\Http\Controllers\Admin\MasterPlanController::class, 'handleChargebeeWebhook'])->name('webhook.chargebee.master-plan');

//cron controller
Route::prefix('cron')->name('admin.')->controller(CronController::class)->group(function () {
    Route::get('/auto_cancel_subscription', 'cancelSusbscriptons');
    Route::get('/test-panel-capacity', 'testPanelCapacityCheck')->name('test.panel.capacity');
    Route::get('/panel-capacity-test', 'showPanelCapacityTest')->name('panel.capacity.test.page');
});

Route::get('/subscription/success', [CustomerPlanController::class, 'subscriptionSuccess'])->name('customer.subscription.success');


Route::post('customer/plans/{id}/subscribe/{encrypted?}', [CustomerPlanController::class, 'initiateSubscription'])->name('customer.plans.subscribe');
Route::middleware(['custom_role:1,2,5'])->prefix('admin')->name('admin.')->group(function () {
    //listing routes
    Route::get('/profile', [AdminController::class, 'profile'])->name('profile'); 
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');

    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
       Route::middleware('view.only')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        
        Route::get('/pricing', [PlanController::class, 'index'])->name('pricing');
        //create admin 
        Route::post('users/store', [AdminController::class, 'store'])->name('users.store');
        Route::post('users/customer/store', [AdminController::class, 'storeCustomer'])->name('users.customer.store');
        Route::get('/{id}/edit', [AdminController::class, 'edit'])->name('edit');
        Route::get('customer/{id}/edit', [AdminController::class, 'userEdit'])->name('user.edit');
        Route::put('/{id}', [AdminController::class, 'update'])->name('update');   // Update
        Route::put('user/{id}', [AdminController::class, 'updateUser'])->name('user.update');   // Update
        Route::delete('/{id}', [AdminController::class, 'destroy'])->name('admin.users.destroy');

        // Plans routes
        Route::resource('plans', PlanController::class);
        Route::get('plans-with-features', [PlanController::class, 'getPlansWithFeatures'])->name('plans.with.features');
        
        // Master Plan routes
        Route::get('master-plan', [App\Http\Controllers\Admin\MasterPlanController::class, 'show'])->name('master-plan.show');
        Route::post('master-plan', [App\Http\Controllers\Admin\MasterPlanController::class, 'store'])->name('master-plan.store');
        Route::get('master-plan/data', [App\Http\Controllers\Admin\MasterPlanController::class, 'data'])->name('master-plan.data');
        Route::get('master-plan/exists', [App\Http\Controllers\Admin\MasterPlanController::class, 'exists'])->name('master-plan.exists');
        Route::post('master-plan/force-sync', [App\Http\Controllers\Admin\MasterPlanController::class, 'forceSync'])->name('master-plan.force-sync');
    
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
        Route::post('/customer/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customer.toggleStatus');
        //orders
        Route::get('/orders/{id}/view', [AdminOrderController::class, 'view'])->name('orders.view');
        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders');
        Route::get('/orders/data', [AdminOrderController::class, 'getOrders'])->name('orders.data');
        Route::get('/orders/card', [AdminOrderController::class, 'indexCard'])->name('orders.card');
        Route::get('/orders/card/data', [AdminOrderController::class, 'getCardOrders'])->name('orders.card.data');
        Route::get('/orders/{id}/split/view', [AdminOrderController::class, 'splitView'])->name('orders.split.view');
        Route::get('/orders/{orderId}/splits', [AdminOrderController::class, 'getOrderSplits'])->name('orders.splits');
        Route::post('/update-order-status', [AdminOrderController::class, 'updateOrderStatus'])->name('orders.updateOrderStatus');
        Route::post('/orders/panel/status/process', [AdminOrderController::class, 'processPanelStatus'])->name('order.panel.status.process');
        Route::get('/orders/{orderId}/emails', [AdminOrderEmailController::class, 'getEmails']);
        Route::post('/subscription/cancel-process', [AdminOrderController::class, 'subscriptionCancelProcess'])->name('order.cancel.process');
        // Split Panel Email routes
        Route::get('/orders/panel/{orderPanelId}/emails', [AdminOrderController::class, 'getSplitEmails']);
        Route::get('/orders/split/{splitId}/export-csv-domains', [AdminOrderController::class, 'exportCsvSplitDomainsById'])->name('orders.split.export.csv.domains');
        Route::post('/orders/{orderId}/assign-to-me', [AdminOrderController::class, 'assignOrderToMe'])->name('orders.assign-to-me');
        Route::post('/orders/{orderId}/change-status', [AdminOrderController::class, 'changeStatus'])->name('orders.change-status');
    
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
        Route::get('/roles/{id}', [CustomRolePermissionController::class, 'getRole'])->name('roles.get');
        Route::get('/role/update',[CustomRolePermissionController::class,'index'])->name('role.update');
        Route::get('/role/destroy',[CustomRolePermissionController::class,'index'])->name('role.destroy');
        //payments
        Route::get('/payments',[CustomRolePermissionController::class,'assign'])->name('payments');
        //settings
        Route::get('/ticket_conversation',[MediaHandlerController::class,'ticket_conversation'])->name('ticket_conversation');
        //profile
        Route::post('profile/update', [App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('profile.update');

        // Support Ticket Routes
        Route::get('/support', [App\Http\Controllers\Admin\SupportTicketController::class, 'index'])->name('support');
        Route::get('/support/tickets', [App\Http\Controllers\Admin\SupportTicketController::class, 'getTickets'])->name('support.tickets');
        Route::get('/support/tickets/{id}', [App\Http\Controllers\Admin\SupportTicketController::class, 'show'])->name('support.tickets.show');
        Route::post('/support/tickets/{id}/reply', [App\Http\Controllers\Admin\SupportTicketController::class, 'reply'])->name('support.tickets.reply');
        Route::patch('/support/tickets/{id}/status', [App\Http\Controllers\Admin\SupportTicketController::class, 'updateStatus'])->name('support.tickets.status');
        Route::get('/subscription-stats', [App\Http\Controllers\Admin\DashboardController::class, 'getSubscriptionStats'])->name('subscription.stats');
        Route::get('/revenue-stats', [App\Http\Controllers\Admin\DashboardController::class, 'getRevenueStats'])->name('revenue.stats');
        Route::get('/ticket-stats', [App\Http\Controllers\Admin\DashboardController::class, 'getTicketStats'])->name('ticket.stats');
        Route::get('/revenue-totals', [App\Http\Controllers\Admin\DashboardController::class, 'getRevenueTotals'])->name('revenue.totals');
        
        //panels
        Route::get('/panels/dashboard', [AdminPanelController::class, 'index'])->name('panels.index');
        Route::get('/panels/data', [AdminPanelController::class, 'getPanelsData'])->name('panels.data');
        Route::get('/panels/{panel}/orders', [AdminPanelController::class, 'getPanelOrders'])->name('panels.orders');
        Route::get('/panels/order-tracking', [AdminPanelController::class, 'getOrderTrackingData'])->name('panels.order-tracking');
        Route::get('/panels/capacity-alert', [AdminPanelController::class, 'getCapacityAlert'])->name('panels.capacity-alert');
        Route::get('/splits/{orderId}/orders', [AdminPanelController::class, 'getOrdersSplits'])->name('orders.splits');
        Route::get('/panels/test', [AdminPanelController::class, 'test'])->name('panels.test');
        //panel crud
        Route::post('/panels/create', [AdminPanelController::class, 'createPanel'])->name('panels.create');
        Route::put('/panels/{id}', [AdminPanelController::class, 'update'])->name('panels.update');
        Route::delete('/panels/{id}', [AdminPanelController::class, 'destroy'])->name('panels.delete');
        Route::post('/panels/run-capacity-check', [AdminPanelController::class, 'runPanelCapacityCheck'])->name('panels.run-capacity-check');
        Route::get('/panels/next-id', [AdminPanelController::class, 'getNextId'])->name('panels.next-id');
       
        // Order Queue Routes
        Route::get('/order_queue', [App\Http\Controllers\Admin\OrderQueueController::class, 'index'])->name('orderQueue.order_queue');
        Route::get('/order_queue/data', [App\Http\Controllers\Admin\OrderQueueController::class, 'getOrdersData'])->name('orderQueue.data');
        Route::get('/order_queue/{orderId}/splits', [App\Http\Controllers\Admin\OrderQueueController::class, 'getOrderSplits'])->name('orderQueue.splits');
        Route::post('/order_queue/{orderId}/assign-to-me', [App\Http\Controllers\Admin\OrderQueueController::class, 'assignOrderToMe'])->name('orderQueue.assign-to-me');
        Route::post('/order_queue/{orderId}/reject', [App\Http\Controllers\Admin\OrderQueueController::class, 'rejectOrder'])->name('orderQueue.reject');
        Route::get('/order_queue/{orderId}/can-reject', [App\Http\Controllers\Admin\OrderQueueController::class, 'canRejectOrder'])->name('orderQueue.can-reject');

        // My Orders
        Route::get('/my-orders', [App\Http\Controllers\Admin\OrderQueueController::class, 'myOrders'])->name('orderQueue.my_orders');
        Route::get('/assigned/order/data', [App\Http\Controllers\Admin\OrderQueueController::class, 'getAssignedOrdersData'])->name('assigned.orders.data');

            //settings
        Route::get('/settings',[AdminSettingsController::class,'index'])->name('settings.index');
        Route::get('/system/config',[AdminSettingsController::class,'sysConfing'])->name('system.config');
        
        // Task Queue Routes
        Route::get('taskInQueue', [App\Http\Controllers\Admin\TaskQueueController::class, 'index'])->name("taskInQueue.index");
        Route::get('taskInQueue/data', [App\Http\Controllers\Admin\TaskQueueController::class, 'getTasksData'])->name("taskInQueue.data");
        Route::post('taskInQueue/{id}/assign', [App\Http\Controllers\Admin\TaskQueueController::class, 'assignTaskToMe'])->name("taskInQueue.assign");
        Route::put('taskInQueue/{id}/status', [App\Http\Controllers\Admin\TaskQueueController::class, 'updateTaskStatus'])->name("taskInQueue.updateStatus");

        // My Task Routes
        Route::get('myTask', [App\Http\Controllers\Admin\MyTaskController::class, 'index'])->name("myTask.index");
        Route::get('myTask/data', [App\Http\Controllers\Admin\MyTaskController::class, 'getMyTasksData'])->name("myTask.data");
        Route::get('myTask/{taskId}/details', [App\Http\Controllers\Admin\MyTaskController::class, 'getTaskDetails'])->name("myTask.details");
        Route::get('myTask/{taskId}/completion-summary', [App\Http\Controllers\Admin\MyTaskController::class, 'getTaskCompletionSummary'])->name("myTask.completion.summary");
        Route::post('myTask/{taskId}/complete', [App\Http\Controllers\Admin\MyTaskController::class, 'completeTask'])->name("myTask.complete");


    }); 

});
Route::post('admin/profile/update', [App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('admin.profile.update');
Route::post('/profile/update-image', [App\Http\Controllers\ProfileController::class, 'updateProfileImage'])->name('profile.update.image');

// Info: Customer Access
Route::get('/customer/orders/new-order/{id}/{encrypted?}', [CustomerOrderController::class, 'newOrder'])->name('customer.orders.new.order');
Route::middleware(['custom_role:3'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/pricing', [CustomerPlanController::class, 'index'])->name('pricing');
    Route::get('/dashboard', [App\Http\Controllers\Customer\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/orders/reorder/{order_id}', [CustomerOrderController::class, 'reorder'])->name('orders.reorder');
    Route::post('/orders/reorder', [CustomerOrderController::class, 'store'])->name('orders.reorder.store');
    Route::post('/orders/run-panel-capacity-check', [CustomerOrderController::class, 'runPanelCapacityCheck'])->name('orders.run-panel-capacity-check');
    Route::get('/orders/{id}/view', [CustomerOrderController::class, 'view'])->name('orders.view');
    // customer.order.edit
    Route::get('/orders/{id}/edit', [CustomerOrderController::class, 'edit'])->name('order.edit');
    Route::get('/orders', [CustomerOrderController::class, 'index'])->name('orders');
    Route::get('/orders/data', [CustomerOrderController::class, 'getOrders'])->name('orders.data');
    // Route::get('/orders/import/{id}', [CustomerOrderController::class, 'getOrderImportData'])->name('orders.import');
    
    // Domain fixing routes for rejected order panels
    Route::get('/orders/{id}/fix-domains', [CustomerOrderController::class, 'showFixDomains'])->name('orders.fix-domains');
    Route::post('/orders/{id}/fix-domains', [CustomerOrderController::class, 'updateFixedDomains'])->name('orders.update-fixed-domains');
  
   
    Route::get('/profile', function () {
        return view('customer.profile.profile');
    })->name('profile');
    Route::get('/settings', function () {
        return view('customer.settings.settings');
    })->name('settings');

    // Plans and pricing routes 
    Route::get('/plans/{id}', [CustomerPlanController::class, 'show'])->name('plans.show');
    Route::get('/plans/{id}/details', [CustomerPlanController::class, 'getPlanDetails'])->name('plans.details');
    Route::post('/plans/{id}/upgrade', [CustomerPlanController::class, 'upgradePlan'])->name('plans.upgrade');
    Route::post('/subscription/cancel-current', [CustomerPlanController::class, 'cancelCurrentSubscription'])->name('subscription.current.cancel');
    Route::post('/plans/update-payment-method', [CustomerPlanController::class, 'updatePaymentMethod'])->name('plans.update-payment-method');
    Route::post('/plans/card-details', [CustomerPlanController::class, 'getCardDetails'])->name('plans.card-details');
    Route::post('/plans/delete-payment-method', [CustomerPlanController::class, 'deletePaymentMethod'])->name('plans.delete-payment-method');
    
    // Subscription handling routes
    Route::get('/subscription/cancel', [CustomerPlanController::class, 'subscriptionCancel'])->name('subscription.cancel');
    Route::post('/subscription/cancel-process', [CustomerPlanController::class, 'subscriptionCancelProcess'])->name('subscription.cancel.process');


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
    Route::get('/orders/emails/{id}/export', [CustomerOrderEmailController::class, 'exportCsv'])->name('orders.email.exportCsv');
    
    // Order Import routes
    Route::get('/orders/import/data', [CustomerOrderController::class, 'getOrdersForImport'])->name('orders.import.data');
    Route::get('/orders/import-data/{id}', [CustomerOrderController::class, 'importOrderData'])->name('orders.import-data');
    //support 
    Route::get('/support', [App\Http\Controllers\Customer\SupportTicketController::class, 'index'])->name('support');
    Route::get('/support/tickets', [App\Http\Controllers\Customer\SupportTicketController::class, 'getTickets'])->name('support.tickets');
    Route::post('/support/tickets', [App\Http\Controllers\Customer\SupportTicketController::class, 'store'])->name('support.tickets.store');
    Route::get('/support/tickets/orders', [App\Http\Controllers\Customer\SupportTicketController::class, 'getUserOrders'])->name('support.tickets.orders');
    Route::get('/support/tickets/{id}', [App\Http\Controllers\Customer\SupportTicketController::class, 'show'])->name('support.tickets.show');
    Route::post('/support/tickets/{id}/reply', [App\Http\Controllers\Customer\SupportTicketController::class, 'reply'])->name('support.tickets.reply');
    Route::post('/update-address', [App\Http\Controllers\Customer\ProfileController::class, 'updateAddress'])->name('address.update');
    

});

// Info: Contractor Access
Route::middleware(['custom_role:4'])->prefix('contractor')->name('contractor.')->group(function () {
    Route::get('/activity/data', [App\Http\Controllers\AppLogController::class, 'getContractorActivity'])->name('activity.data');
    
    // Order Queue Routes
    Route::get('/order_queue', [ContractorOrderQueueController::class, 'index'])->name('orderQueue.order_queue');
    Route::get('/order_queue/data', [ContractorOrderQueueController::class, 'getOrdersData'])->name('orderQueue.data');
    Route::get('/order_queue/{orderId}/splits', [ContractorOrderQueueController::class, 'getOrderSplits'])->name('orderQueue.splits');
    Route::post('/order_queue/{orderId}/assign-to-me', [ContractorOrderQueueController::class, 'assignOrderToMe'])->name('orderQueue.assign-to-me');
    Route::post('/order_queue/{orderId}/reject', [ContractorOrderQueueController::class, 'rejectOrder'])->name('orderQueue.reject');
    Route::get('/order_queue/{orderId}/can-reject', [ContractorOrderQueueController::class, 'canRejectOrder'])->name('orderQueue.can-reject');

    // My Orders
    Route::get('/my-orders', [ContractorOrderQueueController::class, 'myOrders'])->name('orderQueue.my_orders');
    Route::get('/assigned/order/data', [ContractorOrderQueueController::class, 'getAssignedOrdersData'])->name('assigned.orders.data');
    
    Route::get('/orders/{id}/view', [ContractorOrderController::class, 'view'])->name('orders.view');
    Route::get('/orders/{id}/split/view', [ContractorOrderController::class, 'splitView'])->name('orders.split.view');
    
    Route::get('/orders', [ContractorOrderController::class, 'index'])->name('orders');
    Route::get('/orders/data', [ContractorOrderController::class, 'getOrders'])->name('orders.data');
    Route::post('/update-order-status', [ContractorOrderController::class, 'updateStatus'])->name('orders.update.status');
    Route::get('/invoices/data', [ContractorOrderController::class, 'getInvoices'])->name('invoices.data');
    Route::get('/orders/reorder/{order_id}', [ContractorOrderController::class, 'reorder'])->name('orders.reorder');
    Route::post('/order/status/process', [ContractorOrderController::class, 'orderStatusProcess'])->name('order.status.process');
    Route::post('/order-panel/status/process', [ContractorOrderController::class, 'orderPanelStatusProcess'])->name('order.panel.status.process');
    Route::post('/order/email/bulk-import', [ContractorOrderController::class, 'orderImportProcess'])->name('order.email.bulkImport');
    Route::post('/order/panel/email/bulk-import', [ContractorOrderController::class, 'orderSplitImportProcess'])->name('order.panel.email.bulkImport');
    Route::get('/order/panel/{orderPanelId}/email/download-csv', [ContractorOrderController::class, 'downloadPanelCsv'])->name('order.panel.email.downloadCsv');
    
    Route::get('/dashboard', [App\Http\Controllers\Contractor\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/orders-history', [App\Http\Controllers\Contractor\DashboardController::class, 'getOrdersHistory'])->name('orders.history');
    
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
    
    // Split Panel Email routes
    Route::get('/orders/panel/{orderPanelId}/emails', [ContractorOrderController::class, 'getSplitEmails']);
    Route::post('/orders/panel/emails', [ContractorOrderController::class, 'storeSplitEmails']);
    Route::delete('/orders/panel/emails/{id}', [ContractorOrderController::class, 'deleteSplitEmail']);
    
    // CSV Export routes
    Route::get('/orders/{orderId}/export-csv-split-domains', [ContractorOrderController::class, 'exportCsvSplitDomains'])->name('orders.export.csv.split.domains');
    Route::get('/orders/split/{splitId}/export-csv-domains', [ContractorOrderController::class, 'exportCsvSplitDomainsById'])->name('orders.split.export.csv.domains');
    
    // Support ticket routes
    Route::get('/support', [App\Http\Controllers\Contractor\SupportTicketController::class, 'index'])->name('support');
    Route::get('/support/tickets', [App\Http\Controllers\Contractor\SupportTicketController::class, 'getTickets'])->name('support.tickets');
    Route::get('/support/tickets/{id}', [App\Http\Controllers\Contractor\SupportTicketController::class, 'show'])->name('support.tickets.show');
    Route::post('/support/tickets/{id}/reply', [App\Http\Controllers\Contractor\SupportTicketController::class, 'reply'])->name('support.tickets.reply');
    Route::patch('/support/tickets/{id}/status', [App\Http\Controllers\Contractor\SupportTicketController::class, 'updateStatus'])->name('support.tickets.status');
    //panels 
    Route::get('/panels/dashboard', [ContractorPanelController::class, 'index'])->name('panels.index');
    Route::get('/panels/data', [ContractorPanelController::class, 'getOrdersData'])->name('panels.data');
    Route::get('/assigned/order/data', [ContractorOrderController::class, 'getAssignedOrdersData'])->name('panels.data');
    Route::get('/orders/{orderId}/splits', [ContractorPanelController::class, 'getOrderSplits'])->name('orders.splits');
    Route::post('/orders/{orderId}/assign-to-me', [ContractorOrderController::class, 'assignOrderToMe'])->name('orders.assign-to-me');
    Route::post('/orders/{orderId}/change-status', [ContractorOrderController::class, 'changeStatus'])->name('orders.change-status');
    Route::post('/orders/{orderId}/reject', [ContractorPanelController::class, 'rejectOrder'])->name('orders.reject');
    Route::get('/panels/test', [ContractorPanelController::class, 'test'])->name('panels.test');    


    // Domains Removal Tasks
}); 

Route::get('/forget_password', function () {
    return view('admin/auth/forget_password');
});

Route::get('/reset_password', function () {
    return view('admin/auth/reset_password');
});


Route::get('/customers', function () {
    return view('admin/customers/customers');
});

Route::get('/contractor', function () {
    return view('admin/contractor/contractor');
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

Route::get('checkout', function () {
    return view('admin/checkout/index');
})->name("admin.checkout.index");


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
Route::post('/webhook/payment/done', [App\Http\Controllers\Customer\PlanController::class, 'handlePaymentWebhook'])->name('webhook.payment.done');
Route::post('admin/attachments/upload', [App\Http\Controllers\Customer\PlanController::class, 'handleInvoiceWebhook'])->name('admin.quill.image.upload');


// Notification routes
Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
Route::post('/notifications/{id}/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');
Route::get('/notifications/list', [NotificationController::class, 'getNotificationsList'])->middleware(['auth']);
Route::get('/notifications/list/all', [NotificationController::class, 'getNotificationsListAll'])->middleware(['auth']);



// For Development Purpose Only
// Delete order if plan_id is null 
Route::get('/delete-order', [App\Http\Controllers\Customer\OrderController::class, 'deleteAllOrderNullPlanID'])->name('delete.order');
// Fixed Order Status to lowercase
Route::get('/update-order-status-lower-case', [App\Http\Controllers\Customer\OrderController::class, 'updateOrderStatusToLowerCase'])->name('updateOrderStatusToLowerCase');

// Temporary test route to verify panel assignment data
Route::get('/test-panel-assignments', function() {
    $orders = \App\Models\Order::with([
        'user', 
        'plan', 
        'reorderInfo',
        'orderPanels.userOrderPanelAssignments' => function($query) {
            $query->with(['orderPanel', 'orderPanelSplit']);
        }
    ])->get();
    
    $testData = [];
    foreach($orders as $order) {
        $assignmentData = [];
        
        // Check panel assignments
        foreach($order->orderPanels as $orderPanel) {
            foreach($orderPanel->userOrderPanelAssignments as $assignment) {
                $assignmentData[] = [
                    'type' => 'panel',
                    'space_assigned' => $assignment->orderPanel->space_assigned ?? 'N/A',
                    'domains_count' => $assignment->orderPanelSplit->domains ?? 'N/A',
                    'contractor_id' => $assignment->contractor_id
                ];
            }
        }
        
        $testData[] = [
            'order_id' => $order->id,
            'order_name' => $order->user->first_name . ' ' . $order->user->last_name,
            'traditional_assignment' => $order->assigned_to,
            'panel_assignments' => $assignmentData
        ];
    }
    
    return response()->json($testData);
});


// Route for manual execution with parameters
Route::get('/cron/run-draft-notifications', function () {
    try {
        $options = [];
        // Capture command output
        $exitCode = Artisan::call('orders:send-draft-notifications', $options);
        $output = Artisan::output();
        return response()->json([
            'success' => $exitCode === 0,
            'exit_code' => $exitCode,
            'output' => $output,
            'message' => $exitCode === 0 ? 'Command executed successfully' : 'Command failed'
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
})->name('cron.run-draft-notifications');

//
  //notification markers
        Route::get('/customer/notifications/mark-all-as-read',[NotificationController::class, 'markAllAsReadNoti'])->name('notifications.mark-all-as-read');
        Route::get('/customer/notifications/mark-all-as-unread',[NotificationController::class, 'markAllAsUnReadNoti'])->name('notifications.mark-all-as-unread');
        Route::get('/notifications/mark-all-as-read/{id}',[NotificationController::class, 'markAllAsReadById'])->name('notifications.mark-all-as-read-by-id');
        Route::get('/notifications/mark-all-as-unread/{id}',[NotificationController::class, 'markAllAsUnRead'])->name('notifications.mark-all-as-unread-by-id');
  
        //notification markers
        Route::get('/contractor/notifications/mark-all-as-read',[NotificationController::class, 'markAllAsReadNoti'])->name('contractor.notifications.mark-all-as-read');
        Route::get('/contractor/notifications/mark-all-as-unread',[NotificationController::class, 'markAllAsUnReadNoti'])->name('contractor.notifications.mark-all-as-unread');
