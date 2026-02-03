<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\OrderPanel;
use App\Models\OrderPanelSplit;
use App\Models\OrderTracking;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProcessLatePaymentOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:process-late-payments {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process late payment orders: update status and optionally clean up panels based on provider type.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $this->info("🔍 Starting late payment orders processing...");

        // Select orders where is_late_payment is true and not processed
        $orders = Order::where('is_late_payment', true)
            ->where('is_late_payment_processed', false)
            ->get();

        $count = $orders->count();
        $this->info("Found {$count} late payment orders to process.");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($orders as $order) {
            try {
                if ($isDryRun) {
                    $this->info("   [DRY RUN] Would process Order #{$order->id} ({$order->provider_type}) - Status: {$order->status}");
                } else {
                    DB::transaction(function () use ($order) {
                        $providerType = strtolower($order->provider_type ?? '');
                        $status = strtolower($order->status_manage_by_admin ?? '');

                        // Define provider groups
                        $isGoogleOrMicrosoft = Str::contains($providerType, ['google', 'microsoft']);

                        // Logic: Delete splits/panels ONLY if Google/Microsoft AND status is 'removed'
                        if ($isGoogleOrMicrosoft && $status === 'removed') {
                            // Delete OrderPanelSplits
                            OrderPanelSplit::where('order_id', $order->id)->delete();
                            // Delete OrderPanels
                            OrderPanel::where('order_id', $order->id)->delete();
                            // Update OrderTracking status
                            $tracking = OrderTracking::where('order_id', $order->id)->first();
                            if ($tracking) {
                                $tracking->status = 'pending';
                                $tracking->save();
                            }
                            Log::info("Deleted panels and reset tracking for Order #{$order->id} (Provider: {$order->provider_type}, Status: {$order->status_manage_by_admin})");
                        } else {
                            Log::info("Skipped panel deletion/tracking reset for Order #{$order->id} (Provider: {$order->provider_type}, Status: {$order->status_manage_by_admin})");
                        }

                        // Update Order status and processing flag
                        $order->status_manage_by_admin = 'completed';
                        $order->status = 'completed';
                        $order->is_late_payment_processed = true;
                        $order->save();
                    });
                }
            } catch (\Exception $e) {
                $this->error("Error processing Order #{$order->id}: " . $e->getMessage());
                Log::error("ProcessLatePaymentOrders error for order #{$order->id}: " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✅ Late payment orders processed.");

        if (!$isDryRun) {
            $this->info("🚀 Running CheckPanelCapacity command...");
            $this->call('panels:check-capacity');
        }
    }
}
