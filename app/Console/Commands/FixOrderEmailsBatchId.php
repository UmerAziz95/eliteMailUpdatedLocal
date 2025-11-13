<?php

namespace App\Console\Commands;

use App\Models\OrderEmail;
use App\Models\OrderPanel;
use App\Models\OrderPanelSplit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixOrderEmailsBatchId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:fix-batch-ids 
                            {--dry-run : Run without making actual changes}
                            {--order-panel-id= : Fix specific order panel only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix batch_id for existing order_emails records based on 200 emails per batch logic';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $orderPanelId = $this->option('order-panel-id');

        $this->info('Starting batch_id fix process...');
        
        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        try {
            DB::beginTransaction();

            // Get order panels to process
            $query = OrderPanel::with('orderPanelSplits');
            
            if ($orderPanelId) {
                $query->where('id', $orderPanelId);
                $this->info("Processing specific order panel: {$orderPanelId}");
            }
            
            $orderPanels = $query->get();

            if ($orderPanels->isEmpty()) {
                $this->error('No order panels found to process.');
                return Command::FAILURE;
            }

            $this->info("Found {$orderPanels->count()} order panel(s) to process.");

            $totalProcessed = 0;
            $totalUpdated = 0;

            foreach ($orderPanels as $orderPanel) {
                $this->info("\n" . str_repeat('=', 60));
                $this->info("Processing Order Panel ID: {$orderPanel->id}");
                $this->info("Space Assigned: {$orderPanel->space_assigned}");
                
                $splitIds = $orderPanel->orderPanelSplits->pluck('id')->toArray();
                
                if (empty($splitIds)) {
                    $this->warn("  ⚠ No splits found for panel {$orderPanel->id}");
                    continue;
                }

                // Get all emails for this panel's splits that need batch_id assignment
                $emails = OrderEmail::whereIn('order_split_id', $splitIds)
                    ->whereNull('batch_id')
                    ->orderBy('order_split_id')
                    ->orderBy('id')
                    ->get();

                if ($emails->isEmpty()) {
                    $this->info("  ✓ No emails need batch_id assignment for panel {$orderPanel->id}");
                    continue;
                }

                $this->info("  Found {$emails->count()} emails without batch_id");

                // Group emails by split to assign batch_id per split
                $emailsBySplit = $emails->groupBy('order_split_id');

                foreach ($emailsBySplit as $splitId => $splitEmails) {
                    $this->info("\n  Processing Split ID: {$splitId} ({$splitEmails->count()} emails)");
                    
                    $batchNumber = 1;
                    $emailsInCurrentBatch = 0;
                    $updatesForThisSplit = 0;

                    foreach ($splitEmails as $email) {
                        // Assign batch number
                        if (!$isDryRun) {
                            $email->batch_id = $batchNumber;
                            $email->save();
                        }

                        $emailsInCurrentBatch++;
                        $updatesForThisSplit++;

                        // Move to next batch after 200 emails
                        if ($emailsInCurrentBatch >= 200) {
                            $this->line("    ✓ Batch {$batchNumber}: {$emailsInCurrentBatch} emails assigned");
                            $batchNumber++;
                            $emailsInCurrentBatch = 0;
                        }
                    }

                    // Log the last batch if it had emails
                    if ($emailsInCurrentBatch > 0) {
                        $this->line("    ✓ Batch {$batchNumber}: {$emailsInCurrentBatch} emails assigned");
                    }

                    $this->info("  ✓ Split {$splitId}: {$updatesForThisSplit} emails updated across {$batchNumber} batches");
                    $totalUpdated += $updatesForThisSplit;
                }

                $totalProcessed += $emails->count();
            }

            if ($isDryRun) {
                DB::rollBack();
                $this->info("\n" . str_repeat('=', 60));
                $this->warn('🔍 DRY RUN COMPLETED - No changes were made');
                $this->info("Would have updated: {$totalProcessed} emails");
            } else {
                DB::commit();
                $this->info("\n" . str_repeat('=', 60));
                $this->info("✅ Successfully updated {$totalUpdated} emails with batch_id");
                
                Log::info('Order emails batch_id fix completed', [
                    'total_panels_processed' => $orderPanels->count(),
                    'total_emails_updated' => $totalUpdated,
                    'order_panel_id' => $orderPanelId
                ]);
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("\n❌ Error: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            
            Log::error('Error fixing order emails batch_id', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }
}
