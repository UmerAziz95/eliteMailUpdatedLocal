<?php

namespace Database\Seeders;

use App\Models\Pool;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class PoolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some users and plans to reference
        $users = User::limit(3)->get();
        $plans = Plan::limit(2)->get();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please seed users first.');
            return;
        }

        $pools = [
            [
                'user_id' => $users->first()->id,
                'plan_id' => $plans->first()->id ?? null,
                'status' => 'pending',
                'amount' => 299.99,
                'currency' => 'USD',
                'forwarding_url' => 'https://example.com/landing',
                'hosting_platform' => 'GoDaddy',
                'sending_platform' => 'SendGrid',
                'domains' => ['example1.com', 'example2.com', 'example3.com'],
                'total_inboxes' => 100,
                'inboxes_per_domain' => 10,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'master_inbox_email' => 'master@example1.com',
                'master_inbox_confirmation' => true,
                'platform_login' => 'johndoe',
                'platform_password' => 'secure123',
                'additional_info' => 'Sample pool for testing purposes',
            ],
            [
                'user_id' => $users->count() > 1 ? $users->skip(1)->first()->id : $users->first()->id,
                'plan_id' => $plans->count() > 1 ? $plans->skip(1)->first()->id : $plans->first()->id ?? null,
                'status' => 'in_progress',
                'amount' => 599.99,
                'currency' => 'USD',
                'forwarding_url' => 'https://business.com/signup',
                'hosting_platform' => 'Namecheap',
                'sending_platform' => 'Mailgun',
                'domains' => ['business1.com', 'business2.com', 'business3.com', 'business4.com'],
                'total_inboxes' => 200,
                'inboxes_per_domain' => 15,
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'master_inbox_email' => 'master@business1.com',
                'master_inbox_confirmation' => false,
                'platform_login' => 'janesmith',
                'platform_password' => 'business456',
                'is_shared' => true,
                'shared_note' => 'This is a shared pool with multiple contractors',
                'additional_info' => 'High priority business client',
            ]
        ];

        foreach ($pools as $poolData) {
            Pool::create($poolData);
        }

        $this->command->info('Created ' . count($pools) . ' sample pools successfully!');
    }
}
