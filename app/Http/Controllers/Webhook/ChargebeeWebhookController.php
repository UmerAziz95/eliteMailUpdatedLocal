<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\PoolOrder;
use App\Models\Order;
use App\Models\Subscription as UserSubscription;
use App\Services\PoolOrderCancelledService;
use App\Services\OrderCancelledService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ChargebeeWebhookController extends Controller
{
    /**
     * Handle incoming Chargebee webhook
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    
    public function handle(Request $request)
    {
        try {
            // Create log directory if it doesn't exist
            $logPath = storage_path('logs/webhooks');
            if (!file_exists($logPath)) {
                mkdir($logPath, 0755, true);
            }
            
            // Log the incoming webhook to dedicated channel
            Log::channel('chargebee_webhook')->info('Chargebee Webhook Received', [
                'event_type' => $request->input('event_type'),
                'timestamp' => now()->toDateTimeString(),
                'payload' => $request->all(),
                'url' => $request->fullUrl(),
                'method' => $request->method()
            ]);

            // Get the event type
            $eventType = $request->input('event_type');
            $content = $request->input('content', []);

            // Route to appropriate handler based on event type
            switch ($eventType) {
                case 'subscription_renewed':
                    Log::channel('chargebee_webhook')->info('Handling subscription_renewed event');
                    Log::channel('chargebee_webhook')->info('Chargebee Webhook - subscription_renewed event', [
                        'content' => $content
                    ]);
                    // return $this->handleSubscriptionRenewed($content);
                    
                case 'subscription_cancelled':
                    Log::channel('chargebee_webhook')->info('Handling subscription_cancelled event');
                    Log::channel('chargebee_webhook')->info('Chargebee Webhook - subscription_cancelled event', [
                        'content' => $content
                    ]);
                    return $this->handleSubscriptionCancelled($content);
                    
                case 'subscription_changed':
                    Log::channel('chargebee_webhook')->info('Handling subscription_changed event');
                    Log::channel('chargebee_webhook')->info('Chargebee Webhook - subscription_changed event', [
                        'content' => $content
                    ]);
                    // return $this->handleSubscriptionChanged($content);
                    
                default:
                    Log::channel('chargebee_webhook')->info('Chargebee Webhook - Unhandled event type', [
                        'event_type' => $eventType
                    ]);
                    Log::channel('chargebee_webhook')->info('Chargebee Webhook - Unhandled event type', [
                        'content' => $content
                    ]);
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Event type not handled'
                    ], 200);
            }

        } catch (\Exception $e) {
            Log::channel('chargebee_webhook')->error('Chargebee Webhook Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle subscription renewed event (billing cycle completed)
     * This is triggered when a billing cycle completes and subscription renews
     *
     * @param array $content
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleSubscriptionRenewed(array $content)
    {
        try {
            Log::channel('chargebee_webhook')->info('Processing subscription_renewed event');

            // Extract subscription ID from multiple possible locations
            $subscriptionId = null;
            
            // Try to get from subscription object
            if (isset($content['subscription']['id'])) {
                $subscriptionId = $content['subscription']['id'];
            }
            // Try to get from subscription_id field
            elseif (isset($content['subscription_id'])) {
                $subscriptionId = $content['subscription_id'];
            }
            // Try to get from content id directly
            elseif (isset($content['id'])) {
                $subscriptionId = $content['id'];
            }

            if (!$subscriptionId) {
                Log::channel('chargebee_webhook')->warning('Subscription ID not found in webhook payload', [
                    'content' => $content
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription ID not found in payload'
                ], 400);
            }

            Log::channel('chargebee_webhook')->info('Subscription ID extracted', [
                'subscription_id' => $subscriptionId
            ]);

            // Check if this is a pool order subscription
            $poolOrder = PoolOrder::where('chargebee_subscription_id', $subscriptionId)->first();
            
            if ($poolOrder) {
                Log::channel('chargebee_webhook')->info('Pool order found for subscription', [
                    'pool_order_id' => $poolOrder->id,
                    'subscription_id' => $subscriptionId,
                    'current_status' => $poolOrder->status
                ]);

                // Check subscription status in the webhook data
                $subscriptionStatus = $content['subscription']['status'] ?? null;
                
                // If subscription status is cancelled or non-renewing, cancel the pool order
                if (in_array($subscriptionStatus, ['cancelled', 'non_renewing'])) {
                    return $this->cancelPoolOrderSubscription($poolOrder);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Pool order subscription renewed successfully',
                    'pool_order_id' => $poolOrder->id
                ], 200);
            }

            // Check if this is a regular order subscription
            $regularOrder = Order::where('chargebee_subscription_id', $subscriptionId)->first();
            
            if ($regularOrder) {
                Log::channel('chargebee_webhook')->info('Regular order found for subscription', [
                    'order_id' => $regularOrder->id,
                    'subscription_id' => $subscriptionId,
                    'current_status' => $regularOrder->status
                ]);

                // Check subscription status in the webhook data
                $subscriptionStatus = $content['subscription']['status'] ?? null;
                
                // If subscription status is cancelled or non-renewing, cancel the regular order
                if (in_array($subscriptionStatus, ['cancelled', 'non_renewing'])) {
                    return $this->cancelRegularSubscription($regularOrder);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Regular order subscription renewed successfully',
                    'order_id' => $regularOrder->id
                ], 200);
            }

            Log::channel('chargebee_webhook')->warning('No order found for subscription', [
                'subscription_id' => $subscriptionId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No order found for this subscription'
            ], 404);

        } catch (\Exception $e) {
            Log::channel('chargebee_webhook')->error('Error processing subscription_renewed event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process subscription renewal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel pool order subscription using the service
     *
     * @param PoolOrder $poolOrder
     * @return \Illuminate\Http\JsonResponse
     */
    protected function cancelPoolOrderSubscription(PoolOrder $poolOrder)
    {
        Log::channel('chargebee_webhook')->info('Cancelling pool order subscription via webhook', [
            'pool_order_id' => $poolOrder->id,
            'subscription_id' => $poolOrder->chargebee_subscription_id
        ]);

        $cancellationService = new PoolOrderCancelledService();
        $reason = 'Billing cycle completed - subscription cancelled by Chargebee';
        
        $result = $cancellationService->cancelSubscription(
            $poolOrder->id,
            $poolOrder->user_id,
            $reason
        );

        if ($result['success']) {
            Log::channel('chargebee_webhook')->info('Pool order cancelled successfully', [
                'pool_order_id' => $poolOrder->id
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Pool order subscription cancelled successfully',
                'pool_order_id' => $poolOrder->id
            ], 200);
        } else {
            Log::channel('chargebee_webhook')->error('Pool order cancellation failed', [
                'pool_order_id' => $poolOrder->id,
                'error' => $result['message']
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'pool_order_id' => $poolOrder->id
            ], 400);
        }
    }

    /**
     * Cancel regular order subscription using the service
     *
     * @param Order $order
     * @return \Illuminate\Http\JsonResponse
     */
    protected function cancelRegularSubscription(Order $order)
    {
        Log::channel('chargebee_webhook')->info('Cancelling regular order subscription via webhook', [
            'order_id' => $order->id,
            'subscription_id' => $order->chargebee_subscription_id
        ]);

        $cancellationService = new OrderCancelledService();
        $reason = 'Billing cycle completed - subscription cancelled by Chargebee';
        
        $result = $cancellationService->cancelSubscription(
            $order->chargebee_subscription_id,
            $order->user_id,
            $reason,
            false, // remove_accounts
            false  // force_cancel
        );

        if ($result['success']) {
            Log::channel('chargebee_webhook')->info('Regular order cancelled successfully', [
                'order_id' => $order->id
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Regular order subscription cancelled successfully',
                'order_id' => $order->id
            ], 200);
        } else {
            Log::channel('chargebee_webhook')->error('Regular order cancellation failed', [
                'order_id' => $order->id,
                'error' => $result['message']
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'order_id' => $order->id
            ], 400);
        }
    }

    /**
     * Handle subscription cancelled event
     * This is triggered when subscription is explicitly cancelled
     *
     * @param array $content
     * @return \Illuminate\Http\JsonResponse
     */

    protected function handleSubscriptionCancelled(array $content)
    {
        try {
            Log::channel('chargebee_webhook')->info('Processing subscription_cancelled event');

            // Extract subscription ID
            $subscriptionId = $content['subscription']['id'] ?? $content['id'] ?? null;

            if (!$subscriptionId) {
                Log::channel('chargebee_webhook')->warning('Subscription ID not found in cancelled webhook', [
                    'content' => $content
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription ID not found'
                ], 400);
            }

            // Check if this is a pool order subscription
            $poolOrder = PoolOrder::where('chargebee_subscription_id', $subscriptionId)->first();
            
            if ($poolOrder) {
                Log::channel('chargebee_webhook')->info('Pool order found for subscription_cancelled event', [
                    'pool_order_id' => $poolOrder->id,
                    'subscription_id' => $subscriptionId,
                    'current_status' => $poolOrder->status
                ]);

                // Use the cancellation service for proper handling
                return $this->cancelPoolOrderSubscription($poolOrder);
            }

            Log::channel('chargebee_webhook')->warning('No pool order found for cancelled subscription', [
                'subscription_id' => $subscriptionId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No pool order found for this subscription'
            ], 404);

        } catch (\Exception $e) {
            Log::channel('chargebee_webhook')->error('Error processing subscription_cancelled event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process cancellation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle subscription changed event
     *
     * @param array $content
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleSubscriptionChanged(array $content)
    {
        try {
            Log::channel('chargebee_webhook')->info('Processing subscription_changed event', [
                'content' => $content
            ]);

            $subscriptionId = $content['subscription']['id'] ?? $content['id'] ?? null;

            if ($subscriptionId) {
                Log::channel('chargebee_webhook')->info('Subscription changed', [
                    'subscription_id' => $subscriptionId,
                    'changes' => $content
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Subscription change logged'
            ], 200);

        } catch (\Exception $e) {
            Log::channel('chargebee_webhook')->error('Error processing subscription_changed event', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process change: ' . $e->getMessage()
            ], 500);
        }
    }
}
