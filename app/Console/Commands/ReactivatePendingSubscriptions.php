<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SubscriptionReactivation;
use App\Models\Subscription as UserSubscription;
use App\Models\Order;
use App\Services\SubscriptionReactivationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ReactivatePendingSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:reactivate-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reactivate pending subscriptions if their invoice period (end date) has expired.';

    /**
     * Execute the console command.
     */
    public function handle(SubscriptionReactivationService $subscriptionReactivationService)
    {
        $this->info("🔍 Checking for pending subscription reactivations...");

        $pendingReactivations = SubscriptionReactivation::where('status', 'pending')->get();

        if ($pendingReactivations->isEmpty()) {
            $this->info("No pending reactivations found.");
            return;
        }

        $count = $pendingReactivations->count();
        $this->info("Found {$count} pending reactivations.");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($pendingReactivations as $reactivation) {
            try {
                // Find local subscription
                $subscription = UserSubscription::where('chargebee_subscription_id', $reactivation->chargebee_subscription_id)->first();

                if (!$subscription) {
                    $this->error("Local subscription not found for ID: {$reactivation->chargebee_subscription_id}");
                    $reactivation->update([
                        'status' => 'failed',
                        'message' => 'Local subscription not found.',
                    ]);
                    $bar->advance();
                    continue;
                }

                // Check end date using the stored latest_invoice_end_date
                $shouldReactivate = false;
                $now = Carbon::now();

                // Ensure we don't process subscriptions that are not cancelled (unless it's a retry?)
                // Actually if it's pending, it implies previously it was cancelled. 
                // But subscription status might have changed externally?
                if ($subscription->status !== 'cancelled') {
                    $reactivation->update([
                        'status' => 'success', // or 'skipped'
                        'message' => 'Subscription is not cancelled (current status: ' . $subscription->status . '). Marked as handled.',
                    ]);
                    $bar->advance();
                    continue;
                }

                $endDate = $reactivation->latest_invoice_end_date ? Carbon::parse($reactivation->latest_invoice_end_date) : null;

                if ($endDate) {
                    if ($now->gt($endDate)) {
                        $shouldReactivate = true;
                        Log::info("Reactivation #{$reactivation->id}: Invoice end date ({$endDate}) has passed. Proceeding.");
                    } else {
                        Log::info("Reactivation #{$reactivation->id}: Invoice end date ({$endDate}) has NOT passed. Waiting.");
                    }
                } else {
                    // Fallback: If no end_date stored
                    if ($subscription->end_date) {
                        $subEndDate = Carbon::parse($subscription->end_date);
                        if ($now->gt($subEndDate)) {
                            $shouldReactivate = true;
                            Log::info("Reactivation #{$reactivation->id}: Stored date null, but Subscription end date ({$subEndDate}) passed.");
                        }
                    } else {
                        $shouldReactivate = true;
                        Log::warning("Reactivation #{$reactivation->id}: No end date found. Proceeding with reactivation.");
                    }
                }

                if ($shouldReactivate) {
                    $this->info("Reactivating subscription {$reactivation->chargebee_subscription_id} (Attempt " . ($reactivation->retry_count + 1) . ")...");

                    $result = $subscriptionReactivationService->reactivate(
                        $reactivation->chargebee_subscription_id,
                        $reactivation->user_id
                    );

                    if ($result['success']) {
                        $reactivation->update([
                            'status' => 'success',
                            'message' => $result['message'] ?? '',
                            'data' => $result,
                            'latest_invoice_start_date' => isset($result['current_term_start']) ? Carbon::createFromTimestamp($result['current_term_start']) : null,
                            'latest_invoice_end_date' => isset($result['current_term_end']) ? Carbon::createFromTimestamp($result['current_term_end']) : null,
                        ]);
                        $this->info("✅ Reactivation successful.");
                    } else {
                        // Failed
                        $newRetryCount = $reactivation->retry_count + 1;
                        $message = "Reactivation failed (Attempt {$newRetryCount}): " . ($result['message'] ?? 'Unknown error');

                        // User request: "retry 3 time ... when 4th time failed then order cancelled"
                        // This means we allow 3 retries (total 4 attempts).
                        if ($newRetryCount >= 4) {
                            $this->error("❌ Max retries reached (4 failures). Cancelling Order.");

                            $reactivation->update([
                                'status' => 'failed',
                                'message' => "Max retries reached. Order cancelled. Last error: " . ($result['message'] ?? ''),
                                'retry_count' => $newRetryCount,
                                'data' => $result,
                            ]);

                            // Cancel the Order locally
                            $order = Order::find($reactivation->order_id);
                            if ($order) {
                                $order->update([
                                    'status' => 'cancelled',
                                    'status_manage_by_admin' => 'cancelled',
                                ]);
                                Log::info("Order #{$order->id} cancelled due to failed subscription reactivation max retries.");
                            } else {
                                Log::error("Order #{$reactivation->order_id} not found for cancellation.");
                            }

                        } else {
                            // Retry available
                            $reactivation->update([
                                'message' => $message,
                                'retry_count' => $newRetryCount,
                                'data' => $result,
                            ]);
                            $this->warn("⚠️ Reactivation failed. Retrying later (Count: {$newRetryCount}).");
                        }
                    }
                }

            } catch (\Exception $e) {
                Log::error("Error processing reactivation #{$reactivation->id}: " . $e->getMessage());
                $this->error("Exception: " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done.");
    }
}
