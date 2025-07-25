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
                'sub_menu' => []
            ],
            [
                'name' => 'Admins',
                'icon' => 'ti ti-user fs-5',
                'route' => 'admin.index',
                'permission' => 'Admins',
                'sub_menu' => []
            ],
            [
                'name' => 'Customer',
                'icon' => 'ti ti-headphones fs-5',
                'route' => 'admin.customerList',
                'permission' => 'Customer',
                'sub_menu' => []
            ],
            [
                'name' => 'Subscriptions',
                'icon' => 'ti ti-currency-dollar fs-5',
                'route' => 'admin.subs.view',
                'permission' => 'Subscriptions',
                'sub_menu' => []
            ],
            [
                'name' => 'Contractors',
                'icon' => 'ti ti-contract fs-5',
                'route' => 'admin.contractorList',
                'permission' => 'Contractors',
                'sub_menu' => []
            ],
            [
                'name' => 'Invoices',
                'icon' => 'ti ti-file-invoice fs-5',
                'route' => 'admin.invoices.index',
                'permission' => 'Invoices',
                'sub_menu' => []
            ],
            // My orders
            [
                'name' => 'My Orders',
                'icon' => 'ti ti-shopping-cart fs-5',
                'route' => 'admin.orderQueue.my_orders',
                'permission' => 'Orders',
                'sub_menu' => []
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
                    'nested_menu'=>[
                [
                    "name"=>"Card",
                    "icon"=>"ti ti-credit-card fs-5",
                    "route"=>"admin.orders.card",
                    "permission"=>"Order"
                ],
                [
                    "name"=>"List",
                    "icon"=>"ti ti-list fs-5",
                    "route"=>"admin.orders",
                    "permission"=>"Order"
                ]
                ]

            ],
           
            
            [
                'name' => 'Plans',
                'icon' => 'ti ti-devices-dollar fs-5',
                'route' => 'admin.pricing',
                'permission' => 'Plans',
                'sub_menu' => []
            ],
            [
                'name' => 'Roles',
                'icon' => 'ti ti-circles fs-5',
                'route' => 'admin.role.index',
                'permission' => 'Roles',
                'sub_menu' => []
            ],
            [
                        "name" => "Panels",
                        "icon" => "ti ti-file-invoice fs-5",
                        "route" => "admin.panels.index",
                        "permission" => "Panels",
                        'sub_menu' => []
            ],
            [
                        "name" => "Settings",
                        "icon" => "ti ti-file-invoice fs-5",
                        "route" => "admin.system.config",
                        "permission" => "Settings",
                        'sub_menu' => []
            ],
            [
                        "name" => "Discord Settings",
                        "icon" => "ti ti-file-invoice fs-5",
                        "route" => "admin.discord.settings",
                        "permission" => "Discord Settings",
                        'sub_menu' => []
            ]
            // [
            //             "name" => "Coupons",
            //             "icon" => "ti ti-file-invoice fs-5",
            //             "route" => "admin.coupons.index",
            //             "permission" => "Coupons",
            //             'sub_menu' => []
            // ]
        ];
        // sidebar_navigations delete all existing records
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
                'updated_at' => now(),
                'created_at' => now()
            ]
        );

        }

        $extraPermissions=["Mod","Panels"];
        foreach($extraPermissions as $permission){
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
