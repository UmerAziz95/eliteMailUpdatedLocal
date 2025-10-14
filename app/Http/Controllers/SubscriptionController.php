<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OrderCancelledService;

class SubscriptionController extends Controller
{
    protected $orderCancelledService;

    public function __construct(OrderCancelledService $orderCancelledService)
    {
        $this->orderCancelledService = $orderCancelledService;
    }

    public function reactivate($id)
    {
        // Find subscription by ID to get chargebee_subscription_id and user_id
        $subscription = \App\Models\Subscription::find($id);
        
        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription not found'
            ], 404);
        }

        $result = $this->orderCancelledService->reactivateSubscription(
            $subscription->chargebee_subscription_id,
            $subscription->user_id
        );

        if ($result['success']) {
            return response()->json($result, 200);
        }

        return response()->json($result, 400);
    }
}
