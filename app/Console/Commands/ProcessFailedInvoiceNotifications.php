<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Services\SlackNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ProcessFailedInvoiceNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:process-failed-notifications 
                            {--dry-run : Run the command without sending actual notifications}
                            {--force : Force send notifications even if already sent today}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications for invoices that have been in failed status for 72 hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $isForce = $this->option('force');

        $this->info('🔍 Processing failed invoice notifications...');
        
        if ($isDryRun) {
            $this->warn('🧪 DRY RUN MODE - No actual notifications will be sent');
        }

        try {
            // Get invoices that have been in failed status for exactly 72 hours
            $failedInvoices = $this->getFailedInvoicesForNotification($isForce);
            
            if ($failedInvoices->isEmpty()) {
                $this->info('✅ No failed invoices found that need notification.');
                return Command::SUCCESS;
            }

            $this->info("📧 Found {$failedInvoices->count()} failed invoices to notify");
            
            $sentCount = 0;
            $errorCount = 0;
            
            foreach ($failedInvoices as $invoice) {
                try {
                    if ($isDryRun) {
                        $this->line("🔍 [DRY RUN] Would send notification for Invoice #{$invoice->chargebee_invoice_id} (ID: {$invoice->id})");
                        $this->line("   Customer: {$invoice->user->name} ({$invoice->user->email})");
                        $this->line("   Amount: {$invoice->amount} " . ($invoice->order ? $invoice->order->currency : 'USD'));
                        $this->line("   Failed since: " . Carbon::parse($invoice->updated_at)->format('Y-m-d H:i:s T'));
                    } else {
                        // Send the notification
                        $this->sendFailedInvoiceNotification($invoice);
                        
                        // Mark this notification as sent
                        $this->markNotificationSent($invoice);
                        
                        $this->line("✅ Notification sent for Invoice #{$invoice->chargebee_invoice_id}");
                    }
                    
                    $sentCount++;
                    
                } catch (\Exception $e) {
                    $errorCount++;
                    $this->error("❌ Failed to process Invoice #{$invoice->chargebee_invoice_id}: {$e->getMessage()}");
                    
                    Log::error('ProcessFailedInvoiceNotifications: Error processing invoice', [
                        'invoice_id' => $invoice->id,
                        'chargebee_invoice_id' => $invoice->chargebee_invoice_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Summary
            $this->newLine();
            $this->info('📊 Summary:');
            $this->info("   • Total invoices processed: {$failedInvoices->count()}");
            $this->info("   • Notifications sent successfully: {$sentCount}");
            
            if ($errorCount > 0) {
                $this->error("   • Errors encountered: {$errorCount}");
            }
            
            if ($isDryRun) {
                $this->warn('🔍 This was a dry run. No actual notifications were sent.');
            } else {
                $this->info('✅ Failed invoice notification process completed!');
            }
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('❌ Command failed: ' . $e->getMessage());
            Log::error('ProcessFailedInvoiceNotifications: Command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Get invoices that have been in failed status for 72 hours
     */
    private function getFailedInvoicesForNotification(bool $isForce): \Illuminate\Database\Eloquent\Collection
    {
        // Calculate the time 72 hours ago
        $seventyTwoHoursAgo = Carbon::now()->subHours(72);
        
        $query = Invoice::with(['user', 'order', 'order.plan'])
            ->where('status', 'failed')
            ->where('updated_at', '<=', $seventyTwoHoursAgo)
            ->whereHas('user', function ($q) {
                $q->whereNotNull('email');
            });
        
        // If not forced, exclude invoices that already received notification today
        if (!$isForce) {
            $query->where(function ($q) {
                $q->whereNull('cancellation_notification_sent_at')
                  ->orWhere('cancellation_notification_sent_at', '<', Carbon::today());
            });
        }
        
        return $query->get();
    }

    /**
     * Send failed invoice notification to Slack
     */
    private function sendFailedInvoiceNotification(Invoice $invoice): void
    {
        $user = $invoice->user;
        $order = $invoice->order;
        
        // Calculate how long the invoice has been failed
        $failedSince = Carbon::parse($invoice->updated_at);
        $hoursFailed = $failedSince->diffInHours(Carbon::now());
        $daysFailed = round($hoursFailed / 24, 1);
        
        $data = [
            'invoice_id' => $invoice->chargebee_invoice_id,
            'order_id' => $invoice->order_id,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'amount' => $invoice->amount,
            'currency' => $order ? $order->currency : 'USD',
            'plan_name' => $order && $order->plan ? $order->plan->name : 'N/A',
            'failed_since' => $failedSince->format('Y-m-d H:i:s T'),
            'hours_failed' => $hoursFailed,
            'days_failed' => $daysFailed,
            'cancellation_date' => Carbon::now()->addDays(3)->format('Y-m-d'),
            'status' => 'SCHEDULED FOR CANCELLATION'
        ];

        // Prepare the message
        $message = $this->formatCancellationWarningMessage($data);
        
        // Send to Slack
        $result = SlackNotificationService::send('inbox-subscriptions', $message);

        if ($result) {
            Log::channel('slack_notifications')->info('Failed invoice cancellation warning sent', [
                'invoice_id' => $invoice->id,
                'chargebee_invoice_id' => $invoice->chargebee_invoice_id,
                'user_id' => $user->id,
                'hours_failed' => $hoursFailed
            ]);
        } else {
            throw new \Exception('Failed to send Slack notification');
        }
    }

    /**
     * Format cancellation warning message
     */
    private function formatCancellationWarningMessage(array $data): array
    {
        return [
            'text' => "⚠️ *INVOICE CANCELLATION WARNING*",
            'attachments' => [
                [
                    'color' => '#ff9800', // Orange color for warning
                    'fields' => [
                        [
                            'title' => 'Invoice ID',
                            'value' => $data['invoice_id'],
                            'short' => true
                        ],
                        [
                            'title' => 'Order ID',
                            'value' => $data['order_id'] ?? 'N/A',
                            'short' => true
                        ],
                        [
                            'title' => 'Customer Name',
                            'value' => $data['customer_name'],
                            'short' => true
                        ],
                        [
                            'title' => 'Customer Email',
                            'value' => $data['customer_email'],
                            'short' => true
                        ],
                        [
                            'title' => 'Plan',
                            'value' => $data['plan_name'],
                            'short' => true
                        ],
                        [
                            'title' => 'Amount',
                            'value' => $data['currency'] . ' ' . $data['amount'],
                            'short' => true
                        ],
                        [
                            'title' => 'Failed Since',
                            'value' => $data['failed_since'],
                            'short' => true
                        ],
                        // [
                        //     'title' => 'Days Failed',
                        //     'value' => $data['days_failed'] . ' days (' . $data['hours_failed'] . ' hours)',
                        //     'short' => true
                        // ],
                        [
                            'title' => 'Status',
                            'value' => '🚨 ' . $data['status'],
                            'short' => true
                        ],
                        // [
                        //     'title' => 'Scheduled Cancellation Date',
                        //     'value' => '📅 ' . $data['cancellation_date'] . ' (in 3 days)',
                        //     'short' => false
                        // ]
                    ],
                    'footer' => config('app.name', 'ProjectInbox') . ' - Payment Alert System',
                    'ts' => time()
                ]
            ]
        ];
    }

    /**
     * Mark notification as sent
     */
    private function markNotificationSent(Invoice $invoice): void
    {
        $invoice->update([
            'cancellation_notification_sent_at' => Carbon::now()
        ]);
    }
}
