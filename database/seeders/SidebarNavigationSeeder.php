<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SidebarNavigationSeeder extends Seeder
{
    public function run()
    {
        $navs = [
            [ 
                'name' => 'Dashboard',
                'icon' => 'ti ti-home fs-5',
                'route' => 'admin.dashboard',
                'permission' => 'Dashboard',
                'sub_menu' => [],
                'order' => 1
            ],
           [
                "name" => "Order In Queue",
                "icon" => "fa-solid fa-boxes-stacked",   // 📦 multiple orders waiting
                "route" => "admin.orderQueue.order_queue",
                "permission" => "Order Queue",
                'sub_menu' => [],
                'order' => 2
            ],
            [
                "name" => "Cancellations In Queue",
                "icon" => "fa-solid fa-ban",   // 🚫 cancel symbol
                "route" => "admin.taskInQueue.index",
                "permission" => "Task Queue",
                'sub_menu' => [],
                'order' => 3
            ],
            [
                "name" => "My Task",
                "icon" => "fa-solid fa-list-check",   // ✅ personal task list
                "route" => "admin.myTask.index",
                "permission" => "My Task",
                'sub_menu' => [],
                'order' => 4
            ],
            [
                'name' => 'My Orders',
                'icon' => 'ti ti-shopping-cart fs-5',
                'route' => 'admin.orderQueue.my_orders',
                'permission' => 'My Orders',
                'sub_menu' => [],
                'order' => 5
            ],
            
            [
                'name' => 'Orders',
                'icon' => 'ti ti-box fs-5',
                'route' => 'admin.orders',
                'permission' => 'Orders',
                'sub_menu' => [
                    //  [
                    //     "name" => "Order Dashoard",
                    //     "icon" => "ti ti-devices-dollar fs-5",
                    //     "route" => "admin.order.dashboard",
                    //     "permission" => "Orders"
                    // ],
                ],
                'nested_menu' => [
                   
                ],
                'order' => 6
            ],
            [
                'name' => 'Shared Order Requests',
                'icon' => 'fa-solid fa-share-nodes',
                'route' => 'admin.orders.shared-order-requests',
                'permission' => 'Shared Order Requests',
                'sub_menu' => [],
                'order' => 7
            ],
            [
                'name' => 'Plans',
                'icon' => 'ti ti-devices-dollar fs-5',
                'route' => 'admin.pricing',
                'permission' => 'Plans',
                'sub_menu' => [],
                'order' => 8
            ],
            [
                'name' => 'Pool Pricing',
                'icon' => 'ti ti-devices-dollar fs-5',
                'route' => 'admin.pool-pricing.index',
                'permission' => 'Pool Pricing',
                'sub_menu' => [],
                'order' => 8.5
            ],
            // pool panels
            [
                'name' => 'Pool Panels',
                'icon' => 'ti ti-file-invoice fs-5',
                'route' => 'admin.pool-panels.index',
                'permission' => 'Pool Panels',
                'sub_menu' => [],
                'order' => 8.6
            ],
            // pools
            [
                'name' => 'Pools',
                'icon' => 'ti ti-pool fs-5',
                'route' => 'admin.pools.index',
                'permission' => 'Pools',
                'sub_menu' => [],
                'order' => 8.7
            ],
            // [
            //     'name' => 'Special Plans',
            //     'icon' => 'ti ti-star fs-5',
            //     'route' => 'admin.special-plans.index',
            //     'permission' => 'Special Plans',
            //     'sub_menu' => [],
            //     'order' => 8
            // ],
            [
                "name" => "Panels",
                "icon" => "ti ti-file-invoice fs-5",
                "route" => "admin.panels.index",
                "permission" => "Panels",
                'sub_menu' => [],
                'order' => 9
            ],
            [
                'name' => 'Subscriptions',
                'icon' => 'ti ti-currency-dollar fs-5',
                'route' => 'admin.subs.view',
                'permission' => 'Subscriptions',
                'sub_menu' => [],
                'order' => 10
            ],
            [
                'name' => 'Invoices',
                'icon' => 'ti ti-file-invoice fs-5',
                'route' => 'admin.invoices.index',
                'permission' => 'Invoices',
                'sub_menu' => [],
                'order' => 11
            ],
            
            [
                'name' => 'Admins',
                'icon' => 'ti ti-user fs-5',
                'route' => 'admin.index',
                'permission' => 'Admins',
                'sub_menu' => [],
                'order' => 12
            ],
            [
                'name' => 'Customer',
                'icon' => 'ti ti-headphones fs-5',
                'route' => 'admin.customerList',
                'permission' => 'Customer',
                'sub_menu' => [],
                'order' => 13
            ],
            [
                'name' => 'Contractors',
                'icon' => 'ti ti-contract fs-5',
                'route' => 'admin.contractorList',
                'permission' => 'Contractors',
                'sub_menu' => [],
                'order' => 14
            ],
            [
                'name' => 'Roles',
                'icon' => 'ti ti-circles fs-5',
                'route' => 'admin.role.index',
                'permission' => 'Roles',
                'sub_menu' => [],
                'order' => 15
            ],
            [
                "name" => "Internal Order Management",
                "icon" => "ti ti-brand-slack fs-5",
                "route" => "admin.internal_order_management.index",
                "permission" => "Internal Order Management",
                'sub_menu' => [],
                'order' => 16
            ],
            [
                "name" => "Support",
                "icon" => "ti ti-device-mobile-question fs-5",
                "route" => "admin.support",
                "permission" => "Support",
                'sub_menu' => [],
                'order' => 17
            ],
            [
                "name" => "Settings",
                "icon" => "ti ti-settings fs-5",
                "route" => "admin.system.config",
                "permission" => "Settings",
                'sub_menu' => [],
                'order' => 18
            ],
            [
                "name" => "Discord Settings",
                "icon" => "ti ti-brand-discord fs-5",
                "route" => "admin.discord.settings",
                "permission" => "Discord Settings",
                'sub_menu' => [],
                'order' => 19
            ],
            [
                "name" => "Domain Health Dashboard",
                "icon" => "ti ti-heartbeat fs-5",
                "route" => "admin.domain_health_dashboard.index",
                "permission" => "Domain Health Dashboard",
                'sub_menu' => [],
                'order' => 20
            ],
            [
                "name" => "Slack Settings",
                "icon" => "ti ti-brand-slack fs-5",
                "route" => "admin.slack.settings",
                "permission" => "Slack Settings",
                'sub_menu' => [],
                'order' => 21
            ],
            [
                "name" => "Error Logs",
                "icon" => "ti ti-bug fs-5",
                "route" => "admin.error-logs.index",
                "permission" => "Error Logs",
                'sub_menu' => [],
                'order' => 22
            ],
            [
                "name" => "System Logs",
                "icon" => "ti ti-file-text fs-5",
                "route" => "admin.logs.index",
                "permission" => "System Logs",
                'sub_menu' => [],
                'order' => 23
            ],
            [
                "name" => "GHL Settings",
                "icon" => "ti ti-settings fs-5",
                "route" => "admin.ghl-settings.index",
                "permission" => "GHL Settings",
                'sub_menu' => [],
                'order' => 24
            ],
            // Disclaimer
            [
                "name" => "Disclaimers",
                "icon" => "ti ti-alert-circle fs-5",
                "route" => "admin.disclaimers.index",
                "permission" => "Disclaimers",
                'sub_menu' => [],
                'order' => 25
            ],
            [
                "name" => "Team Leader Dashboard",
                "icon" => "ti ti-dashboard fs-5",
                "route" => "admin.team_leader.dashboard",
                "permission" => "Team Leader Dashboard",
                'sub_menu' => []
            ]

        ];

        // Delete all existing records
        DB::table('sidebar_navigations')->truncate();

        foreach ($navs as $nav) {
            // Insert or update permission (prevent duplicates)
            DB::table('permissions')->updateOrInsert(
                ['name' => $nav['permission']],
                [
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );

            // Insert or update navigation
            DB::table('sidebar_navigations')->updateOrInsert(
                ['name' => $nav['name']], // unique constraint
                [
                    'icon' => $nav['icon'],
                    'route' => $nav['route'],
                    'permission' => $nav['permission'],
                    'sub_menu' => json_encode($nav['sub_menu']),
                    'nested_menu' => isset($nav['nested_menu']) ? json_encode($nav['nested_menu']) : null,
                    'order' => isset($nav['order']) ? $nav['order'] : 0,
                    'updated_at' => now(),
                    'created_at' => now()
                ]
            );
        }

        $extraPermissions = ["Mod", "Panels", "Order Queue", "Task Queue", "My Task", "Support", "GHL Settings", "Error Logs", "System Logs", "Order Reassign", "Special Plans"];
        foreach ($extraPermissions as $permission) {
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

        $this->call(AssignRoleSeeder::class);
    }
}  
