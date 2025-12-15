<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PoolOrder;
use App\Services\PoolOrderAssignmentService;
use Illuminate\Support\Facades\Log;

class RetryPoolOrderAssignment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pool:retry-assignment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry auto-assignment for pool orders that failed due to insufficient capacity';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(PoolOrderAssignmentService $assignmentService)
    {
        $this->info('Starting pool order assignment retry...');
        Log::info('Command pool:retry-assignment started.');

        // Find orders that need assignment
        // Criteria:
        // 1. Status is draft or pending (admin status)
        // 2. Has a sending platform selected (implies intent to use auto-assignment)
        // 3. Has NO domains assigned yet
        $orders = PoolOrder::whereIn('status_manage_by_admin', ['draft', 'pending'])
            ->whereNotNull('sending_platform')
            ->where(function ($query) {
                $query->whereNull('domains')
                      ->orWhere('domains', '[]')
                      ->orWhere('domains', '');
            })
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No pending orders found requiring assignment.');
            return 0;
        }

        $this->info("Found {$orders->count()} orders to process.");

        foreach ($orders as $order) {
            $this->info("Processing Order #{$order->id}...");
            try {
                // Determine if this order actually *wants* auto-assignment.
                // If 'sending_platform' is set, we assume yes based on Controller logic.
                
                $result = $assignmentService->autoAssignDomains($order);

                if ($result['success']) {
                    $this->info("SUCCESS: Assigned domains to Order #{$order->id}");
                    Log::info("Command retry success: Order #{$order->id}");
                } else {
                    $this->warn("FAILED: Order #{$order->id} - " . $result['message']);
                }
            } catch (\Exception $e) {
                $this->error("ERROR processing Order #{$order->id}: " . $e->getMessage());
                Log::error("Command retry error for Order #{$order->id}: " . $e->getMessage());
            }
        }

        $this->info('Retry process completed.');
        Log::info('Command pool:retry-assignment completed.');

        return 0;
    }
}
