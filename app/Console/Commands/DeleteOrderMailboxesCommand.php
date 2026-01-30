<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderProviderSplit;
use App\Services\OrderCancelledService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DeleteOrderMailboxesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:delete-mailboxes {order_id : The order ID to delete mailboxes for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete mailboxes for an order using the same provider/split flow as cancellation (Mailin, PremiumInboxes, Mailrun, or Google/365)';

    /**
     * Execute the console command.
     * Uses the same routing as OrderCancelledService::deleteOrderMailboxes and calls
     * the same functions: deleteMailboxesFromProviderSplits, deleteSmtpOrderMailboxes,
     * or deleteGoogle365OrderMailboxes.
     */
    public function handle(OrderCancelledService $orderCancelledService): int
    {
        $orderId = $this->argument('order_id');

        $this->info("Starting mailbox deletion for Order #{$orderId}...");

        Log::channel('mailin-ai')->info('DeleteOrderMailboxesCommand: Starting mailbox deletion', [
            'action' => 'delete_order_mailboxes_cmd',
            'order_id' => $orderId,
        ]);

        $order = Order::find($orderId);
        if (!$order) {
            $this->error("Order #{$orderId} not found.");
            return 1;
        }

        $providerType = $order->provider_type ?? 'unknown';
        $this->info("Order provider type: {$providerType}");

        try {
            // Same routing as OrderCancelledService::deleteOrderMailboxes (provider type + splits)
            if (in_array(strtolower($providerType ?? ''), ['private smtp', 'smtp'])) {
                $providerSplits = OrderProviderSplit::where('order_id', $order->id)->get();

                if ($providerSplits->isNotEmpty()) {
                    $this->info('Calling deleteMailboxesFromProviderSplits (Mailin / PremiumInboxes / Mailrun).');
                    $orderCancelledService->deleteMailboxesFromProviderSplits($order, $providerSplits);
                } else {
                    $this->info('Calling deleteSmtpOrderMailboxes (legacy order_emails).');
                    $orderCancelledService->deleteSmtpOrderMailboxes($order);
                }
            } elseif (in_array($providerType, ['Google', 'Microsoft 365'])) {
                $this->info('Calling deleteGoogle365OrderMailboxes (sync).');
                $orderCancelledService->deleteGoogle365OrderMailboxes($order, null, 0);
            } else {
                $this->warn("Provider type '{$providerType}' is not supported for mailbox deletion.");
                Log::channel('mailin-ai')->warning('DeleteOrderMailboxesCommand: Unsupported provider type', [
                    'action' => 'delete_order_mailboxes_cmd',
                    'order_id' => $orderId,
                    'provider_type' => $providerType,
                ]);
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("Mailbox deletion failed: {$e->getMessage()}");
            Log::channel('mailin-ai')->error('DeleteOrderMailboxesCommand: Mailbox deletion failed', [
                'action' => 'delete_order_mailboxes_cmd',
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }

        $this->info("Mailbox deletion completed for Order #{$orderId}. Check logs for per-provider details.");

        Log::channel('mailin-ai')->info('DeleteOrderMailboxesCommand: Completed mailbox deletion', [
            'action' => 'delete_order_mailboxes_cmd',
            'order_id' => $orderId,
            'provider_type' => $providerType,
        ]);

        return 0;
    }
}
