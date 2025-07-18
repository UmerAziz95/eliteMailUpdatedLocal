<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DomainRemovalTask;
use App\Models\User;
use App\Models\Order;
use Carbon\Carbon;

class DomainRemovalTaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some existing users and orders
        $users = User::limit(5)->get();
        $orders = Order::limit(10)->get();
        
        if ($users->isEmpty() || $orders->isEmpty()) {
            $this->command->warn('No users or orders found. Please create some users and orders first.');
            return;
        }

        // Create sample domain removal tasks
        for ($i = 1; $i <= 15; $i++) {
            $user = $users->random();
            $order = $orders->random();
            
            // Create different scenarios
            $scenarios = [
                // Pending tasks (should show in queue)
                [
                    'status' => 'pending',
                    'assigned_to' => null,
                    'started_queue_date' => Carbon::now()->addDays(rand(1, 7)), // Future dates
                ],
                // Overdue pending tasks
                [
                    'status' => 'pending', 
                    'assigned_to' => null,
                    'started_queue_date' => Carbon::now()->subDays(rand(1, 3)), // Past dates
                ],
                // In-progress tasks
                [
                    'status' => 'in-progress',
                    'assigned_to' => $users->random()->id,
                    'started_queue_date' => Carbon::now()->subDays(rand(1, 5)),
                ],
                // Completed tasks
                [
                    'status' => 'completed',
                    'assigned_to' => $users->random()->id,
                    'started_queue_date' => Carbon::now()->subDays(rand(5, 15)),
                ],
            ];
            
            $scenario = $scenarios[$i % count($scenarios)];
            
            DomainRemovalTask::create([
                'started_queue_date' => $scenario['started_queue_date'],
                'user_id' => $user->id,
                'order_id' => $order->id,
                'chargebee_subscription_id' => 'sub_' . uniqid(),
                'reason' => $this->getRandomReason(),
                'assigned_to' => $scenario['assigned_to'],
                'status' => $scenario['status'],
                'created_at' => Carbon::now()->subDays(rand(0, 30)),
                'updated_at' => Carbon::now()->subDays(rand(0, 5)),
            ]);
        }
        
        $this->command->info('Domain removal tasks seeded successfully!');
    }
    
    private function getRandomReason(): string
    {
        $reasons = [
            'Customer requested subscription cancellation',
            'Payment failed multiple times',
            'Customer violated terms of service',
            'Subscription expired and not renewed',
            'Customer requested refund',
            'Technical issues with service',
            'Customer switched to different plan',
            'Business closure',
        ];
        
        return $reasons[array_rand($reasons)];
    }
}
