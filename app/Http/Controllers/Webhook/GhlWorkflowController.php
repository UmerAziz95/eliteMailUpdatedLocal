<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class GhlWorkflowController extends Controller
{
    /**
     * Handle GHL workflow webhook for payment failure check
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkFailedPaymentCounter(Request $request)
    {
        try {
            // Log the incoming webhook data for debugging
            Log::info('GHL Workflow Webhook - Failed Payment Counter Check', [
                'request_data' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            // Validate the request structure
            $validator = Validator::make($request->all(), [
                'failed_payment_counter' => 'required|numeric'
            ]);

            if ($validator->fails()) {
                Log::error('GHL Workflow Webhook - Validation failed', [
                    'errors' => $validator->errors(),
                    'request_data' => $request->all()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid request structure',
                    'errors' => $validator->errors()
                ], 400);
            }
            
            // Get the failed payment counter
            $failedPaymentCounter = (int) $request->input('failed_payment_counter', 0);
            $failedPaymentCounter = $failedPaymentCounter + 1;
            
            // Check if failed payment counter is less than or equal to 72
            $isEligible = $failedPaymentCounter <= 72;

            Log::info('GHL Workflow Webhook - Payment Counter Check Result', [
                'failed_payment_counter' => $failedPaymentCounter,
                'is_eligible' => $isEligible,
                'threshold' => 72
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'is_eligible' => $isEligible,
                    'failed_payment_counter' => $failedPaymentCounter,
                    'threshold' => 72,
                    'message' => $isEligible 
                        ? 'Payment counter is within acceptable range' 
                        : 'Payment counter exceeds acceptable range'
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('GHL Workflow Webhook - Exception occurred', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle general GHL workflow webhook
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleWorkflow(Request $request)
    {
        try {
            // Log the incoming webhook data
            Log::info('GHL Workflow Webhook - General Handler', [
                'request_data' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            // Basic webhook acknowledgment
            return response()->json([
                'success' => true,
                'message' => 'Webhook received successfully',
                'timestamp' => now()->toISOString()
            ], 200);

        } catch (\Exception $e) {
            Log::error('GHL Workflow Webhook - General Handler Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify webhook signature (if GHL sends signatures)
     *
     * @param Request $request
     * @param string $secret
     * @return bool
     */
    protected function verifyWebhookSignature(Request $request, string $secret): bool
    {
        $signature = $request->header('X-GHL-Signature') ?? $request->header('X-Webhook-Signature');
        
        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($signature, $expectedSignature);
    }
}
