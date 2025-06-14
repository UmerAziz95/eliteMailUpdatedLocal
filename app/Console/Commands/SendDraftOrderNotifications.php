<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\User;
use App\Mail\DraftOrderNotificationMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendDraftOrderNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:send-draft-notifications 
                            {--dry-run : Run without sending actual emails}
                            {--force : Force send even if already sent today}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily notifications to customers for orders in draft status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $isForce = $this->option('force');
        
        $this->info('🚀 Starting draft order notification process...');
        
        // Build query for draft orders
        $query = Order::where('status_manage_by_admin', 'draft')
            ->with('user')
            ->whereHas('user', function ($q) {
                $q->whereNotNull('email');
            });
        
        // If not forced, exclude orders that already received notification today
        if (!$isForce) {
            $query->where(function ($q) {
                $q->whereNull('last_draft_notification_sent_at')
                  ->orWhere('last_draft_notification_sent_at', '<', Carbon::today());
            });
        }
        
        $draftOrders = $query->get();
        
        if ($draftOrders->isEmpty()) {
            $this->info('✅ No draft orders found that need notification.');
            return Command::SUCCESS;
        }
        
        $this->info("📧 Found {$draftOrders->count()} draft orders to notify");
        
        $sentCount = 0;
        $errorCount = 0;
        
        foreach ($draftOrders as $order) {
            try {
                if (!$order->user) {
                    $this->warn("⚠️  Order #{$order->id} has no associated user. Skipping...");
                    continue;
                }
                
                if ($isDryRun) {
                    $orderId = $order->chargebee_invoice_id ?? $order->id;
                    $this->line("🔍 [DRY RUN] Would send email to: {$order->user->email} for Order #{$orderId}");
                } else {
                    // Send the email
                    $order->user->email = "contact.farooq.raaj@gmail.com";
                    
                    Mail::to($order->user->email)->send(new DraftOrderNotificationMail($order, $order->user));
                    
                    // Update the notification timestamp
                    $order->update(['last_draft_notification_sent_at' => Carbon::now()]);
                    
                    $orderId = $order->chargebee_invoice_id ?? $order->id;
                    $this->line("✅ Email sent to: {$order->user->email} for Order #{$orderId}");
                }
                
                $sentCount++;
                
            } catch (\Exception $e) {
                $errorCount++;
                $orderId = $order->chargebee_invoice_id ?? $order->id;
                $this->error("❌ Failed to send email for Order #{$orderId}: {$e->getMessage()}");
            }
        }
        
        // Summary
        $this->newLine();
        $this->info('📊 Summary:');
        $this->info("   • Total orders processed: {$draftOrders->count()}");
        $this->info("   • Emails sent successfully: {$sentCount}");
        
        if ($errorCount > 0) {
            $this->error("   • Errors encountered: {$errorCount}");
        }
        
        if ($isDryRun) {
            $this->warn('🔍 This was a dry run. No actual emails were sent.');
        } else {
            $this->info('✅ Draft order notification process completed!');
        }
        
        return Command::SUCCESS;
    }
}
