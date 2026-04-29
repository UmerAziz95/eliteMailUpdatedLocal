<?php

namespace App\Console\Commands;

use App\Models\OrderEmail;
use App\Models\OrderPanel;
use App\Models\Subscription;
use App\Services\OrderCancelledService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteExpiredMailboxes extends Command
{
    protected $signature = 'mailboxes:delete-expired {--limit=25} {--sleep-ms=3000}';

    protected $description = 'Delete Mailin.ai mailboxes for EOBC cancelled subscriptions that have reached their end date (Private SMTP + Google/365)';

    public function handle(): int
    {
        $this->info('Starting expired mailbox deletion process for EOBC cancelled subscriptions...');

        try {
            if (!config('mailin_ai.automation_enabled', false)) {
                $this->info('Mailin.ai automation is disabled. Skipping mailbox deletion.');

                Log::channel('mailin-ai')->info('Skipping expired mailbox deletion - automation disabled', [
                    'action' => 'delete_expired_mailboxes',
                ]);

                return self::SUCCESS;
            }

            $limit = max((int) $this->option('limit'), 1);
            $sleepMs = max((int) $this->option('sleep-ms'), 0);

            $cancelledService = app(OrderCancelledService::class);

            $privateSmtpStats = $this->processPrivateSmtpOrders($cancelledService, $limit, $sleepMs);
            $google365Stats = $this->processGoogle365Orders($cancelledService, $limit, $sleepMs);

            $this->newLine();
            $this->info('=== Final Summary ===');
            $this->line("Private SMTP => Found: {$privateSmtpStats['found']}, Processed: {$privateSmtpStats['processed']}, Skipped: {$privateSmtpStats['skipped']}, Errors: {$privateSmtpStats['errors']}");
            $this->line("Google/365   => Found: {$google365Stats['found']}, Processed: {$google365Stats['processed']}, Skipped: {$google365Stats['skipped']}, Errors: {$google365Stats['errors']}");

            Log::channel('mailin-ai')->info('Expired mailbox deletion command completed', [
                'action' => 'delete_expired_mailboxes',
                'private_smtp' => $privateSmtpStats,
                'google_365' => $google365Stats,
                'sleep_ms' => $sleepMs,
                'limit' => $limit,
            ]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Fatal error in expired mailbox deletion: ' . $e->getMessage());

            Log::channel('mailin-ai')->error('Fatal error in expired mailbox deletion process', [
                'action' => 'delete_expired_mailboxes',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    protected function processPrivateSmtpOrders(OrderCancelledService $cancelledService, int $limit, int $sleepMs): array
    {
        $this->newLine();
        $this->info('--- Processing Private SMTP expired EOBC orders ---');

        $stats = [
            'found' => 0,
            'processed' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        $subscriptions = Subscription::query()
            ->where('subscriptions.status', 'cancelled')
            ->where('subscriptions.is_cancelled_force', false)
            ->whereNotNull('subscriptions.end_date')
            ->where('subscriptions.end_date', '<=', now())
            ->join('orders', 'orders.chargebee_subscription_id', '=', 'subscriptions.chargebee_subscription_id')
            ->where('orders.provider_type', 'Private SMTP')
            ->where('orders.status_manage_by_admin', '!=', 'removed')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('order_emails')
                    ->whereColumn('order_emails.order_id', 'orders.id')
                    ->whereNotNull('order_emails.mailin_mailbox_id');
            })
            ->select('subscriptions.*')
            ->distinct()
            ->with('order')
            ->limit($limit)
            ->get();

        $stats['found'] = $subscriptions->count();

        $this->info("Found {$stats['found']} Private SMTP expired EOBC subscriptions to process.");

        Log::channel('mailin-ai')->info('Found expired Private SMTP subscriptions for mailbox deletion', [
            'action' => 'delete_expired_mailboxes_private_smtp',
            'count' => $stats['found'],
            'subscription_ids' => $subscriptions->pluck('id')->toArray(),
            'limit' => $limit,
        ]);

        foreach ($subscriptions as $subscription) {
            $result = $this->processSingleSubscription(
                subscription: $subscription,
                expectedProviderTypes: ['Private SMTP'],
                resourceExistsCallback: function ($order): bool {
                    return OrderEmail::where('order_id', $order->id)
                        ->whereNotNull('mailin_mailbox_id')
                        ->exists();
                },
                resourceLabel: 'mailboxes',
                actionName: 'delete_expired_mailboxes_private_smtp',
                cancelledService: $cancelledService
            );

            $stats[$result]++;

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
        }

        $this->info("Private SMTP: Processed: {$stats['processed']}, Skipped: {$stats['skipped']}, Errors: {$stats['errors']}");

        Log::channel('mailin-ai')->info('Private SMTP expired mailbox deletion completed', [
            'action' => 'delete_expired_mailboxes_private_smtp',
            'stats' => $stats,
        ]);

        return $stats;
    }

    protected function processGoogle365Orders(OrderCancelledService $cancelledService, int $limit, int $sleepMs): array
    {
        $this->newLine();
        $this->info('--- Processing Google / Microsoft 365 expired EOBC orders ---');

        $stats = [
            'found' => 0,
            'processed' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        $subscriptions = Subscription::query()
            ->where('subscriptions.status', 'cancelled')
            ->where('subscriptions.is_cancelled_force', false)
            ->whereNotNull('subscriptions.end_date')
            ->where('subscriptions.end_date', '<=', now())
            ->join('orders', 'orders.chargebee_subscription_id', '=', 'subscriptions.chargebee_subscription_id')
            ->whereIn('orders.provider_type', ['Google', 'Microsoft 365'])
            ->where('orders.status_manage_by_admin', '!=', 'removed')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('order_panel')
                    ->whereColumn('order_panel.order_id', 'orders.id');
            })
            ->select('subscriptions.*')
            ->distinct()
            ->with('order')
            ->limit($limit)
            ->get();

        $stats['found'] = $subscriptions->count();

        $this->info("Found {$stats['found']} Google/Microsoft 365 expired EOBC subscriptions to process.");

        Log::channel('mailin-ai')->info('Found expired Google/Microsoft 365 subscriptions for mailbox deletion', [
            'action' => 'delete_expired_mailboxes_google365',
            'count' => $stats['found'],
            'subscription_ids' => $subscriptions->pluck('id')->toArray(),
            'limit' => $limit,
        ]);

        foreach ($subscriptions as $subscription) {
            $result = $this->processSingleSubscription(
                subscription: $subscription,
                expectedProviderTypes: ['Google', 'Microsoft 365'],
                resourceExistsCallback: function ($order): bool {
                    return OrderPanel::where('order_id', $order->id)->exists();
                },
                resourceLabel: 'panels',
                actionName: 'delete_expired_mailboxes_google365',
                cancelledService: $cancelledService
            );

            $stats[$result]++;

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
        }

        $this->info("Google/365: Processed: {$stats['processed']}, Skipped: {$stats['skipped']}, Errors: {$stats['errors']}");

        Log::channel('mailin-ai')->info('Google/365 expired mailbox deletion completed', [
            'action' => 'delete_expired_mailboxes_google365',
            'stats' => $stats,
        ]);

        return $stats;
    }

    protected function processSingleSubscription(
        Subscription $subscription,
        array $expectedProviderTypes,
        callable $resourceExistsCallback,
        string $resourceLabel,
        string $actionName,
        OrderCancelledService $cancelledService
    ): string {
        try {
            $order = $subscription->order;

            if (!$order) {
                $this->warn("No order found for subscription ID: {$subscription->id}");

                Log::channel('mailin-ai')->warning('No order found for expired subscription', [
                    'action' => $actionName,
                    'subscription_id' => $subscription->id,
                    'chargebee_subscription_id' => $subscription->chargebee_subscription_id,
                ]);

                return 'skipped';
            }

            if (!in_array($order->provider_type, $expectedProviderTypes, true)) {
                $this->info("Skipping order #{$order->id} - provider_type is '{$order->provider_type}'");

                Log::channel('mailin-ai')->info('Skipping order - provider type mismatch', [
                    'action' => $actionName,
                    'order_id' => $order->id,
                    'subscription_id' => $subscription->id,
                    'provider_type' => $order->provider_type,
                    'expected_provider_types' => $expectedProviderTypes,
                ]);

                return 'skipped';
            }

            if (strtolower((string) $order->status_manage_by_admin) === 'removed') {
                $this->info("Skipping order #{$order->id} - status_manage_by_admin is removed");

                Log::channel('mailin-ai')->info('Skipping order - admin status is removed', [
                    'action' => $actionName,
                    'order_id' => $order->id,
                    'subscription_id' => $subscription->id,
                    'status_manage_by_admin' => $order->status_manage_by_admin,
                ]);

                return 'skipped';
            }

            $hasResources = $resourceExistsCallback($order);

            if (!$hasResources) {
                $this->info("Skipping order #{$order->id} - no {$resourceLabel} found (already deleted)");

                Log::channel('mailin-ai')->info('Skipping order - resources already deleted', [
                    'action' => $actionName,
                    'order_id' => $order->id,
                    'subscription_id' => $subscription->id,
                    'resource_label' => $resourceLabel,
                ]);

                return 'skipped';
            }

            $this->info("Processing order #{$order->id} for subscription #{$subscription->id} (end_date: {$subscription->end_date})");

            Log::channel('mailin-ai')->info('Starting mailbox deletion for order', [
                'action' => $actionName,
                'order_id' => $order->id,
                'subscription_id' => $subscription->id,
                'provider_type' => $order->provider_type,
                'end_date' => $subscription->end_date,
            ]);

            $cancelledService->deleteOrderMailboxes($order);

            Log::channel('mailin-ai')->info('Successfully processed expired subscription for mailbox deletion', [
                'action' => $actionName,
                'order_id' => $order->id,
                'subscription_id' => $subscription->id,
                'end_date' => $subscription->end_date,
                'provider_type' => $order->provider_type,
            ]);

            return 'processed';
        } catch (\Throwable $e) {
            $this->error("Error processing subscription #{$subscription->id}: {$e->getMessage()}");

            Log::channel('mailin-ai')->error('Error processing expired subscription for mailbox deletion', [
                'action' => $actionName,
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 'errors';
        }
    }
}