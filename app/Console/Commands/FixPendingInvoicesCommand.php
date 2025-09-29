<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Subscription as UserSubscription;
use App\Services\ActivityLogService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvoiceGeneratedMail;

class FixPendingInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:fix-pending 
                           {--invoice-id= : Specific invoice ID to check}
                           {--days=7 : Number of days to check back for pending invoices}
                           {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix pending invoices by checking their status with ChargeBee and updating accordingly';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting invoice status fix process...');
        
        $invoiceId = $this->option('invoice-id');
        $days = $this->option('days');
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('Running in DRY RUN mode - no changes will be made');
        }

        try {
            $invoices = $this->getPendingInvoices($invoiceId, $days);
            
            if ($invoices->isEmpty()) {
                $this->info('No pending invoices found to process.');
                return 0;
            }

            $this->info("Found {$invoices->count()} pending invoice(s) to check...");
            
            $progressBar = $this->output->createProgressBar($invoices->count());
            $progressBar->start();

            $updated = 0;
            $errors = 0;

            foreach ($invoices as $invoice) {
                try {
                    $result = $this->checkAndUpdateInvoice($invoice, $dryRun);
                    if ($result) {
                        $updated++;
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("Error processing invoice {$invoice->chargebee_invoice_id}: " . $e->getMessage());
                    Log::error('Error in FixPendingInvoicesCommand', [
                        'invoice_id' => $invoice->id,
                        'chargebee_invoice_id' => $invoice->chargebee_invoice_id,
                        'error' => $e->getMessage()
                    ]);
                }
                
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();

            // Summary
            $this->info("Process completed!");
            $this->table(
                ['Status', 'Count'],
                [
                    ['Checked', $invoices->count()],
                    ['Updated', $updated],
                    ['Errors', $errors],
                    ['Mode', $dryRun ? 'DRY RUN' : 'LIVE']
                ]
            );

            return 0;

        } catch (\Exception $e) {
            $this->error('Command failed: ' . $e->getMessage());
            Log::error('FixPendingInvoicesCommand failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Get pending invoices to process
     */
    private function getPendingInvoices($invoiceId = null, $days = 7)
    {
        $query = Invoice::whereIn('status', ['pending', 'failed']);

        if ($invoiceId) {
            $query->where('chargebee_invoice_id', $invoiceId);
        } else {
            $query->where('created_at', '>=', Carbon::now()->subDays($days));
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Check invoice status with ChargeBee and update if necessary
     */
    private function checkAndUpdateInvoice(Invoice $invoice, $dryRun = false)
    {
        $this->line("Checking invoice: {$invoice->chargebee_invoice_id}");

        try {
            // Retrieve invoice from ChargeBee
            $chargebeeInvoice = \ChargeBee\ChargeBee\Models\Invoice::retrieve($invoice->chargebee_invoice_id);
            $invoiceData = $chargebeeInvoice->invoice()->getValues();

            $this->info("  ChargeBee Status: {$invoiceData['status']}");
            $this->info("  Current Status: {$invoice->status}");

            // Map ChargeBee status to our system status
            $newStatus = $this->mapInvoiceStatus($invoiceData['status'], 'invoice_updated');

            if ($invoice->status === $newStatus) {
                $this->comment("  No change needed - status already correct");
                return false;
            }

            if ($dryRun) {
                $this->warn("  [DRY RUN] Would update status from '{$invoice->status}' to '{$newStatus}'");
                return false;
            }

            // Update the invoice
            $oldStatus = $invoice->status;
            $subscriptionId = $invoiceData['subscription_id'] ?? null;
            $customerId = $invoiceData['customer_id'] ?? null;

            $invoice->update([
                'status' => $newStatus,
                'paid_at' => isset($invoiceData['paid_at']) 
                    ? Carbon::createFromTimestamp($invoiceData['paid_at'])->toDateTimeString() 
                    : $invoice->paid_at,
                'amount' => isset($invoiceData['amount_paid']) ? ($invoiceData['amount_paid'] / 100) : $invoice->amount,
                'metadata' => json_encode(['invoice' => $invoiceData]),
                'updated_at' => now('UTC'),
            ]);

            // Update subscription billing dates if invoice is now paid
            if ($newStatus === 'paid' && isset($invoiceData['paid_at']) && $subscriptionId) {
                $subscription = UserSubscription::where('chargebee_subscription_id', $subscriptionId)->first();
                if ($subscription) {
                    $subscription->update([
                        'last_billing_date' => Carbon::createFromTimestamp($invoiceData['paid_at'])->toDateTimeString(),
                        'next_billing_date' => Carbon::createFromTimestamp($invoiceData['paid_at'])->addMonth()->addDay()->toDateTimeString()
                    ]);
                    $this->info("  Updated subscription billing dates");
                }
            }

            // Remove payment failure records if invoice is now paid
            if ($newStatus === 'paid' && $subscriptionId && $customerId) {
                try {
                    $deletedRecords = DB::table('payment_failures')
                        ->where('chargebee_subscription_id', $subscriptionId)
                        ->where('chargebee_customer_id', $customerId)
                        ->where('created_at', '>=', now('UTC')->subHours(72))
                        ->delete();

                    if ($deletedRecords > 0) {
                        $this->info("  Cleared {$deletedRecords} payment failure record(s)");
                    }
                } catch (\Exception $e) {
                    $this->warn("  Failed to clear payment failure records: " . $e->getMessage());
                }
            }

            // Send email notification for important status changes
            if ($oldStatus !== $newStatus && in_array($newStatus, ['paid', 'failed'])) {
                try {
                    $user = User::find($invoice->user_id);
                    if ($user) {
                        // Send email to user
                        Mail::to($user->email)
                            ->queue(new InvoiceGeneratedMail(
                                $invoice,
                                $user,
                                false
                            ));

                        // Send email to admin
                        Mail::to(config('mail.admin_address', 'admin@example.com'))
                            ->queue(new InvoiceGeneratedMail(
                                $invoice,
                                $user,
                                true
                            ));

                        $this->info("  Queued email notifications");
                    }
                } catch (\Exception $e) {
                    $this->warn("  Failed to send email notifications: " . $e->getMessage());
                }
            }

            // Log the update
            Log::info('Invoice status fixed via command', [
                'invoice_id' => $invoice->id,
                'chargebee_invoice_id' => $invoice->chargebee_invoice_id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'command' => 'invoices:fix-pending'
            ]);

            // Create activity log
            ActivityLogService::log(
                'customer-invoice-fixed',
                'Invoice status fixed via command: ' . $invoice->id,
                $invoice,
                [
                    'user_id' => $invoice->user_id,
                    'invoice_id' => $invoice->id,
                    'chargebee_invoice_id' => $invoice->chargebee_invoice_id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'fixed_by' => 'system-command',
                    'amount' => $invoice->amount,
                    'paid_at' => $invoice->paid_at,
                ],
                $invoice->user_id
            );

            $this->info("  ✅ Updated status from '{$oldStatus}' to '{$newStatus}'");
            return true;

        } catch (\ChargeBee\ChargeBee\Exceptions\InvalidRequestException $e) {
            $this->error("  ❌ ChargeBee API Error: " . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            $this->error("  ❌ General Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Map ChargeBee invoice status to our system status
     * Same logic as in PlanController
     */
    private function mapInvoiceStatus($chargebeeStatus, $eventType)
    {
        switch ($chargebeeStatus) {
            case 'paid':
                return 'paid';
            case 'payment_due':
                return 'pending';
            case 'voided':
                return 'voided';
            case 'not_paid':
                return $eventType === 'invoice_payment_failed' ? 'failed' : 'pending';
            default:
                return 'pending';
        }
    }
}