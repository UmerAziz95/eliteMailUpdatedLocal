<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use ChargeBee\ChargeBee\Models\Subscription as CBSubscription;

class CronController extends Controller
{
    /**
     * Auto-cancel pending orders older than 3 days
     */
    public function cancelSubscriptions()
    {
        $thresholdDate = Carbon::now()->subDays(3);

        $orders = Order::where('status_manage_by_admin', 'Pending')
            ->where('created_at', '<=', $thresholdDate)
            ->get();

        foreach ($orders as $order) {
            $this->subscriptionCancelProcess($order->chargebee_subscription_id);
        }

        return response()->json([
            'success' => true,
            'message' => count($orders) . ' pending subscriptions processed for cancellation.'
        ]);
    }

    /**
     * Cancel a subscription and update related data
     */
    public function subscriptionCancelProcess($chargebee_subscription_id)
    {
        $subscription = Subscription::where('chargebee_subscription_id', $chargebee_subscription_id)->first();

        if (!$subscription || $subscription->status !== 'active') {
            return false;
        }

        try {
            $result = CBSubscription::cancelForItems($chargebee_subscription_id, [
                "end_of_term" => false,
                "credit_option" => "none",
                "unbilled_charges_option" => "delete",
                "account_receivables_handling" => "no_action"
            ]);

            $subscriptionData = $result->subscription();

            if ($subscriptionData->status === 'cancelled') {
                // Update subscription
                $subscription->update([
                    'status' => 'cancelled',
                    'cancellation_at' => now(),
                    'reason' => "Auto-cancelled by system after 3 days in pending status",
                    'end_date' => $this->getEndExpiryDate($subscription->start_date),
                ]);

                // Update user
                $user = User::find($subscription->user_id);
                if ($user) {
                    $user->update([
                        'subscription_status' => 'cancelled',
                        'subscription_id' => null,
                        'plan_id' => null,
                    ]);
                }

                // Update order
                $order = Order::where('chargebee_subscription_id', $chargebee_subscription_id)->first();
                if ($order) {
                    $order->update([
                        'status_manage_by_admin' => 'Cancelled',
                    ]);
                }
            }

        } catch (\Throwable $e) {
            \Log::error('Auto cancellation failed for subscription: ' . $chargebee_subscription_id . ' | ' . $e->getMessage());
        }

        return true;
    }

    /**
     * Get calculated end expiry date (assuming this is implemented elsewhere)
     */
    protected function getEndExpiryDate($startDate)
    {
        return Carbon::parse($startDate)->addMonth(); // Adjust this logic as per your actual subscription cycle
    }

    /**
     * Test route to run the panel capacity check command
     */
    public function testPanelCapacityCheck(Request $request)
    {
        try {
            $isDryRun = $request->has('dry_run') && $request->dry_run == '1';
            $isForce = $request->has('force') && $request->force == '1';
            
            // Build command options
            $options = [];
            if ($isDryRun) {
                $options['--dry-run'] = true;
            }
            if ($isForce) {
                $options['--force'] = true;
            }
            
            // Capture output using output buffering
            ob_start();
            
            try {
                // Execute the command using Laravel's Artisan facade
                $returnCode = \Illuminate\Support\Facades\Artisan::call('panels:check-capacity', $options);
                $output = \Illuminate\Support\Facades\Artisan::output();
            } catch (\Exception $e) {
                $output = 'Command execution failed: ' . $e->getMessage();
                $returnCode = 1;
            }
            
            ob_end_clean();
            
            // Split output into lines for better display
            $outputLines = array_filter(explode("\n", trim($output)));
            
            return response()->json([
                'success' => $returnCode === 0,
                'command' => 'panels:check-capacity ' . implode(' ', array_keys($options)),
                'return_code' => $returnCode,
                'output' => $outputLines,
                'raw_output' => $output,
                'options' => [
                    'dry_run' => $isDryRun,
                    'force' => $isForce
                ],
                'timestamp' => Carbon::now()->format('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => Carbon::now()->format('Y-m-d H:i:s')
            ], 500);
        }
    }

    /**
     * Display the panel capacity test page
     */
    public function showPanelCapacityTest()
    {
        return view('test.panel-capacity');
    }
}

