<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderProviderSplit;
use App\Services\MailAutomation\MailboxCreationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PremiumInboxesWebhookController extends Controller
{
    /**
     * Handle PremiumInboxes webhook events
     */
    public function handle(Request $request)
    {
        // Verify webhook signature
        if (!$this->verifySignature($request)) {
            Log::channel('mailin-ai')->warning('PremiumInboxes webhook signature verification failed', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $event = $request->input('event');
        $orderId = $request->input('order_id');
        $clientOrderId = $request->input('client_order_id');
        $data = $request->input('data', []);

        Log::channel('mailin-ai')->info('PremiumInboxes webhook received', [
            'event' => $event,
            'order_id' => $orderId,
            'client_order_id' => $clientOrderId,
        ]);

        // Extract our order_id from client_order_id
        // Format: "order-{order_id}-premiuminboxes"
        if (preg_match('/^order-(\d+)-premiuminboxes$/', $clientOrderId, $matches)) {
            $ourOrderId = $matches[1];
            $order = Order::find($ourOrderId);

            if (!$order) {
                Log::channel('mailin-ai')->error('Order not found for PremiumInboxes webhook', [
                    'client_order_id' => $clientOrderId,
                    'our_order_id' => $ourOrderId,
                ]);
                return response()->json(['error' => 'Order not found'], 404);
            }

            // Find the provider split
            $split = OrderProviderSplit::where('order_id', $ourOrderId)
                ->where('provider_slug', 'premiuminboxes')
                ->where('external_order_id', $orderId)
                ->first();

            if (!$split) {
                Log::channel('mailin-ai')->error('OrderProviderSplit not found', [
                    'order_id' => $ourOrderId,
                    'premiuminboxes_order_id' => $orderId,
                ]);
                return response()->json(['error' => 'Split not found'], 404);
            }

            // Handle event
            switch ($event) {
                case 'order.ns_validated':
                    $this->handleNsValidated($order, $split, $data);
                    break;

                case 'order.active':
                    $this->handleOrderActive($order, $split, $data);
                    break;

                case 'order.buildout_issue':
                    $this->handleBuildoutIssue($order, $split, $data);
                    break;

                default:
                    Log::channel('mailin-ai')->warning('Unknown PremiumInboxes webhook event', [
                        'event' => $event,
                        'order_id' => $ourOrderId,
                    ]);
            }

            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Invalid client_order_id format'], 400);
    }

    /**
     * Handle order.ns_validated event
     */
    private function handleNsValidated(Order $order, OrderProviderSplit $split, array $data): void
    {
        // Update domain statuses to active
        foreach ($data['domains'] ?? [] as $domainData) {
            $domain = $domainData['domain'] ?? null;
            if ($domain && ($domainData['ns_status'] ?? '') === 'validated') {
                $split->setDomainStatus($domain, 'active');
            }
        }

        // Check if all domains are active
        $split->checkAndUpdateAllDomainsActive();

        Log::channel('mailin-ai')->info('PremiumInboxes nameservers validated', [
            'order_id' => $order->id,
            'domains' => $data['domains'] ?? [],
        ]);
    }

    /**
     * Handle order.active event
     */
    private function handleOrderActive(Order $order, OrderProviderSplit $split, array $data): void
    {
        // Update split status
        $split->update([
            'order_status' => 'active',
            'webhook_received_at' => now(),
        ]);

        // Fetch and store mailboxes
        $mailboxService = new MailboxCreationService();
        $reorderInfo = $order->reorderInfo()->first();
        
        if (!$reorderInfo) {
            Log::channel('mailin-ai')->error('No reorder info found for PremiumInboxes webhook', [
                'order_id' => $order->id,
            ]);
            return;
        }

        // Extract prefix variants
        $prefixVariants = $reorderInfo->prefix_variants ?? [];
        if (is_string($prefixVariants)) {
            $prefixVariants = json_decode($prefixVariants, true) ?? [];
        }
        $prefixVariants = array_values(array_filter($prefixVariants));
        
        $prefixVariantsDetails = $reorderInfo->prefix_variants_details ?? [];
        if (is_string($prefixVariantsDetails)) {
            $prefixVariantsDetails = json_decode($prefixVariantsDetails, true) ?? [];
        }

        $result = $mailboxService->createMailboxesForSplit(
            $order,
            $split,
            $prefixVariants,
            $prefixVariantsDetails
        );

        Log::channel('mailin-ai')->info('PremiumInboxes order active, mailboxes fetched', [
            'order_id' => $order->id,
            'mailboxes_created' => count($result['created'] ?? []),
            'mailboxes_failed' => count($result['failed'] ?? []),
        ]);

        // Check if all splits are complete
        $this->checkOrderCompletion($order);
    }

    /**
     * Handle order.buildout_issue event
     */
    private function handleBuildoutIssue(Order $order, OrderProviderSplit $split, array $data): void
    {
        // Mark domains as failed
        foreach ($split->domains ?? [] as $domain) {
            $split->setDomainStatus($domain, 'failed');
        }

        $split->update([
            'order_status' => 'buildout_issue',
            'webhook_received_at' => now(),
        ]);

        // Reject order or mark for manual review
        $order->update([
            'status_manage_by_admin' => 'reject',
            'reason' => 'PremiumInboxes buildout issue: ' . ($data['reason'] ?? 'Unknown error'),
        ]);

        Log::channel('mailin-ai')->error('PremiumInboxes buildout issue', [
            'order_id' => $order->id,
            'reason' => $data['reason'] ?? 'Unknown',
        ]);
    }

    /**
     * Verify webhook signature
     */
    private function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Webhook-Signature');
        $payload = $request->getContent();
        $secret = config('premiuminboxes.webhook_secret');

        if (!$signature || !$secret) {
            return false;
        }

        // Extract sha256 hash from signature
        // Format: "sha256={hash}"
        if (!preg_match('/sha256=(.+)/', $signature, $matches)) {
            return false;
        }

        $receivedHash = $matches[1];
        $expectedHash = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedHash, $receivedHash);
    }

    /**
     * Check if order is complete (all providers done)
     */
    private function checkOrderCompletion(Order $order): void
    {
        // Check if all provider splits have mailboxes created
        $splits = OrderProviderSplit::where('order_id', $order->id)->get();
        
        $allComplete = true;
        foreach ($splits as $split) {
            if ($split->provider_slug === 'premiuminboxes') {
                // Check if order is active
                if ($split->order_status !== 'active') {
                    $allComplete = false;
                    break;
                }
            } else {
                // Mailin.ai - check if mailboxes exist
                $mailboxes = $split->getAllMailboxes();
                if (empty($mailboxes)) {
                    $allComplete = false;
                    break;
                }
            }
        }

        if ($allComplete) {
            $order->update([
                'status_manage_by_admin' => 'completed',
                'completed_at' => now(),
            ]);

            Log::channel('mailin-ai')->info('Order completed after all providers finished', [
                'order_id' => $order->id,
            ]);
        }
    }
}
