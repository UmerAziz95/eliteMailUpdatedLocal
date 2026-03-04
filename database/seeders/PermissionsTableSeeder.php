<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            'Dashboard',
            'Order Queue',
            'Task Queue',
            'My Task',
            'My Orders',
            'Orders',
            'Shared Order Requests',
            'Plans',
            'Pool Pricing',
            'Pool Panels',
            'Pools',
            'Pool Domains',
            'Panels',
            'Subscriptions',
            'Invoices',
            'Admins',
            'Customer',
            'Contractors',
            'Roles',
            'Internal Order Management',
            'Support',
            'Settings',
            'Discord Settings',
            'Domain Health Dashboard',
            'Slack Settings',
            'Error Logs',
            'System Logs',
            'GHL Settings',
            'Disclaimers',
            'Team Leader Dashboard',
            'Smtp Providers',
            'Mod',
            'Order Reassign',
            'Special Plans',
            'Verify Order',
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                [
                    'name' => $permission,
                    'guard_name' => 'web'
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }
    }
}
