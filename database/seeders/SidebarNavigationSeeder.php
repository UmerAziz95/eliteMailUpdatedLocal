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
                "icon" => "fa-brands fa-first-order",
                "route" => "admin.orderQueue.order_queue",
                "permission" => "Order Queue",
                'sub_menu' => [],
                'order' => 2
            ],
            [
                "name" => "Task In Queue",
                "icon" => "fa-brands fa-first-order",
                "route" => "admin.taskInQueue.index",
                "permission" => "Task Queue",
                'sub_menu' => [],
                'order' => 3
            ],
            [
                "name" => "My Task",
                "icon" => "fa-brands fa-first-order",
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
                    [
                        "name" => "Plans",
                        "icon" => "ti ti-devices-dollar fs-5",
                        "route" => "admin.pricing",
                        "permission" => "Plans"
                    ],
                    [
                        "name" => "Orders",
                        "icon" => "ti ti-box fs-5",
                        "route" => "admin.orders",
                        "permission" => "Orders"
                    ],
                    [
                        "name" => "Subscriptions",
                        "icon" => "ti ti-currency-dollar fs-5",
                        "route" => "admin.subs.view",
                        "permission" => "Subscriptions"
                    ],
                    [
                        "name" => "Invoices",
                        "icon" => "ti ti-file-invoice fs-5",
                        "route" => "admin.invoices.index",
                        "permission" => "Invoices"
                    ],
                    [
                        "name" => "Panels",
                        "icon" => "ti ti-file-invoice fs-5",
                        "route" => "admin.panels.index",
                        "permission" => "Panels"
                    ]
                ],
                'nested_menu' => [
                    [
                        "name" => "Card",
                        "icon" => "ti ti-credit-card fs-5",
                        "route" => "admin.orders.card",
                        "permission" => "Order"
                    ],
                    [
                        "name" => "List",
                        "icon" => "ti ti-list fs-5",
                        "route" => "admin.orders",
                        "permission" => "Order"
                    ]
                ],
                'order' => 6
            ],
            [
                'name' => 'Plans',
                'icon' => 'ti ti-devices-dollar fs-5',
                'route' => 'admin.pricing',
                'permission' => 'Plans',
                'sub_menu' => [],
                'order' => 7
            ],
            [
                "name" => "Panels",
                "icon" => "ti ti-file-invoice fs-5",
                "route" => "admin.panels.index",
                "permission" => "Panels",
                'sub_menu' => [],
                'order' => 8
            ],
            [
                'name' => 'Subscriptions',
                'icon' => 'ti ti-currency-dollar fs-5',
                'route' => 'admin.subs.view',
                'permission' => 'Subscriptions',
                'sub_menu' => [],
                'order' => 9
            ],
            [
                'name' => 'Invoices',
                'icon' => 'ti ti-file-invoice fs-5',
                'route' => 'admin.invoices.index',
                'permission' => 'Invoices',
                'sub_menu' => [],
                'order' => 10
            ],
            
            [
                'name' => 'Admins',
                'icon' => 'ti ti-user fs-5',
                'route' => 'admin.index',
                'permission' => 'Admins',
                'sub_menu' => [],
                'order' => 11
            ],
            [
                'name' => 'Customer',
                'icon' => 'ti ti-headphones fs-5',
                'route' => 'admin.customerList',
                'permission' => 'Customer',
                'sub_menu' => [],
                'order' => 12
            ],
            [
                'name' => 'Contractors',
                'icon' => 'ti ti-contract fs-5',
                'route' => 'admin.contractorList',
                'permission' => 'Contractors',
                'sub_menu' => [],
                'order' => 13
            ],
            [
                'name' => 'Roles',
                'icon' => 'ti ti-circles fs-5',
                'route' => 'admin.role.index',
                'permission' => 'Roles',
                'sub_menu' => [],
                'order' => 14
            ],
            [
                "name" => "Internal Order Management",
                "icon" => "ti ti-brand-slack fs-5",
                "route" => "admin.internal_order_management.index",
                "permission" => "Internal Order Management",
                'sub_menu' => [],
                'order' => 15
            ],
            [
                "name" => "Support",
                "icon" => "ti ti-device-mobile-question fs-5",
                "route" => "admin.support",
                "permission" => "Support",
                'sub_menu' => [],
                'order' => 16
            ],
            [
                "name" => "Settings",
                "icon" => "ti ti-settings fs-5",
                "route" => "admin.system.config",
                "permission" => "Settings",
                'sub_menu' => [],
                'order' => 17
            ],
            [
                "name" => "Discord Settings",
                "icon" => "ti ti-brand-discord fs-5",
                "route" => "admin.discord.settings",
                "permission" => "Discord Settings",
                'sub_menu' => [],
                'order' => 18
            ],
            [
                "name" => "Domain Health Dashboard",
                "icon" => "ti ti-heartbeat fs-5",
                "route" => "admin.domain_health_dashboard.index",
                "permission" => "Domain Health Dashboard",
                'sub_menu' => [],
                'order' => 19
            ],
            [
                "name" => "Slack Settings",
                "icon" => "ti ti-brand-slack fs-5",
                "route" => "admin.slack.settings",
                "permission" => "Slack Settings",
                'sub_menu' => [],
                'order' => 20
            ],
            [
                "name" => "Error Logs",
                "icon" => "ti ti-bug fs-5",
                "route" => "admin.error-logs.index",
                "permission" => "Error Logs",
                'sub_menu' => [],
                'order' => 21
            ],
            [
                "name" => "System Logs",
                "icon" => "ti ti-file-text fs-5",
                "route" => "admin.logs.index",
                "permission" => "System Logs",
                'sub_menu' => [],
                'order' => 22
            ],
            [
                "name" => "GHL Settings",
                "icon" => "ti ti-settings fs-5",
                "route" => "admin.ghl-settings.index",
                "permission" => "GHL Settings",
                'sub_menu' => [],
                'order' => 23
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

        $extraPermissions = ["Mod", "Panels", "Order Queue", "Task Queue", "My Task", "Support", "GHL Settings", "Error Logs", "System Logs", "Order Reassign"];
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
