<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Http\Controllers\Webhook\GhlWorkflowController;
use App\Http\Controllers\Webhook\ChargebeeWebhookController;

/*
|--------------------------------------------------------------------------
| Webhook Routes
|--------------------------------------------------------------------------
|
| Here is where you can register webhook routes for your application.
| These routes handle incoming webhooks from external services and
| should not require authentication or CSRF protection.
|
*/

/*
|--------------------------------------------------------------------------
| GHL (Go High Level) Webhook Routes
|--------------------------------------------------------------------------
*/

// GHL workflow webhook for checking failed payment counter
Route::post('/ghl/workflow/check-payment-counter', [GhlWorkflowController::class, 'checkFailedPaymentCounter'])
    ->name('ghl.workflow.check-payment-counter');

// General GHL workflow webhook handler
Route::post('/ghl/workflow', [GhlWorkflowController::class, 'handleWorkflow'])
    ->name('ghl.workflow.handle');
// test route for GHL webhook
Route::get('/ghl/test', function (Request $request) {
    return response()->json([
        'success' => true,
        'message' => 'GHL Test Webhook received successfully'
    ]);
})->name('ghl.test.webhook');

/*
|--------------------------------------------------------------------------
| Chargebee Webhook Routes
|--------------------------------------------------------------------------
*/

// Main Chargebee webhook handler for subscription events
Route::post('/chargebee/billing-cycle', [ChargebeeWebhookController::class, 'handle'])
    ->name('chargebee.webhook.billing-cycle');

// Alternative Chargebee webhook endpoint
Route::post('/chargebee/webhook', [ChargebeeWebhookController::class, 'handle'])
    ->name('chargebee.webhook.handle');

// Test route for Chargebee webhook
Route::get('/chargebee/test', function (Request $request) {
    return response()->json([
        'success' => true,
        'message' => 'Chargebee Test Webhook received successfully',
        'timestamp' => now()->toDateTimeString()
    ]);
})->name('chargebee.test.webhook');

