<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SubscriptionReactivation;
use App\Models\Subscription as UserSubscription;
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

                // Check end date
                // If end_date is null, we assume we can proceed (or should we wait? usually cancelled subs have end_date)
                $shouldReactivate = false;
                $now = Carbon::now();

                if ($subscription->status !== 'cancelled') {
                    // Already active or something else? Mark as processed to avoid loops
                    $reactivation->update([
                        'status' => 'success', // or 'skipped'
                        'message' => 'Subscription is not cancelled (current status: ' . $subscription->status . '). Marked as handled.',
                    ]);
                    $bar->advance();
                    continue;
                }

                // Check end date using the stored latest_invoice_end_date
                $shouldReactivate = false;
                $now = Carbon::now();

                $endDate = $reactivation->latest_invoice_end_date ? Carbon::parse($reactivation->latest_invoice_end_date) : null;

                if ($endDate) {
                    if ($now->gt($endDate)) {
                        $shouldReactivate = true;
                        Log::info("Reactivation #{$reactivation->id}: Invoice end date ({$endDate}) has passed. Proceeding.");
                    } else {
                        Log::info("Reactivation #{$reactivation->id}: Invoice end date ({$endDate}) has NOT passed. Waiting.");
                    }
                } else {
                    // Fallback: If no end_date stored, check subscription or reactivate immediately?
                    // Let's stick to the stored date logic. If null, maybe we should fetch subscription as backup or just proceed?
                    // User asked to base on these dates. If null, let's look at subscription as fallback or warn.
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
                    $this->info("Reactivating subscription {$reactivation->chargebee_subscription_id}...");

                    $result = $subscriptionReactivationService->reactivate(
                        $reactivation->chargebee_subscription_id,
                        $reactivation->user_id
                    );

                    $reactivation->update([
                        'status' => $result['success'] ? 'success' : 'failed',
                        'message' => $result['message'] ?? '',
                        'data' => $result, // Store full result
                        'latest_invoice_start_date' => isset($result['current_term_start']) ? Carbon::createFromTimestamp($result['current_term_start']) : null,
                        'latest_invoice_end_date' => isset($result['current_term_end']) ? Carbon::createFromTimestamp($result['current_term_end']) : null,
                    ]);

                    if ($result['success']) {
                        $this->info("✅ Reactivation successful.");
                    } else {
                        $this->error("❌ Reactivation failed: " . ($result['message'] ?? 'Unknown error'));
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
