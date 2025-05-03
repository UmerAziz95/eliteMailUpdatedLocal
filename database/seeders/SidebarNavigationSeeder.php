<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SidebarNavigationSeeder extends Seeder
{
    public function run()
    {
        // navs with array with menu items and sub menu items added to it
        $navs = [
            [
                'name' => 'Dashboard',
                'icon' => 'ti ti-home fs-5',
                'route' => 'admin.dashboard',
                'sub_menu' => [],
                'permission' => 'Dashboard'
            ],
            [
                'name' => 'Admins',
                'icon' => 'ti ti-user fs-5',
                'route' => 'admin.index',
                'permission' => 'Admins',
                'sub_menu' => [
                 
                ]
            ],
            [
                'name' => 'Customer',
                'icon' => 'ti ti-headphones fs-5',
                'route' => 'admin.customer',
                'permission' => 'Customer',
                'sub_menu' => [
                 
                ]
            ],
            [
                'name' => 'Subscriptions',
                'icon' => 'ti ti-currency-dollar fs-5',
                'route' => 'admin.subs.view',
                'permission' => 'Subscriptions',
                'sub_menu' => [
                 
                ]
            ],
         
            [
                'name' => 'Contractors',
                'icon' => 'ti ti-contract fs-5',
                'route' => 'admin.contractorList',
                // Payments
                'permission' => 'Contractors',
                'sub_menu' => [
                 
                ]
            ],
            [
                'name' => 'Invoices',
                'icon' => 'ti ti-file-invoice fs-5',
                'route' => 'admin.invoices.index',
                // Payments
                'permission' => 'Invoices',
                'sub_menu' => [
                 
                ]
            ],
            [
                'name' => 'Orders',
                'icon' => 'ti ti-box fs-5',
                'route' => 'admin.orders',
                // Payments
                'permission' => 'Orders',
                'sub_menu' => [
                 
                ]
            ],
            [
                'name' => 'Plans',
                'icon' => 'ti ti-devices-dollar fs-5',
                'route' => 'admin.pricing',
                // Payments
                'permission' => 'Plans',
                'sub_menu' => [
                 
                ]
            ],
          
         
            // admin.setting
            [
                'name' => 'Roles',
                'icon' => 'ti ti-circles fs-5',
                'route' => 'admin.role.index',
                // Settings
                'permission' => 'Roles',
                'sub_menu' => [
                  
                ]
            ],
         
            
            
        ];
        // add menus on database
        foreach ($navs as $nav) {
            $nav_id = DB::table('sidebar_navigations')->insertGetId([
                'name' => $nav['name'],
                'icon' => $nav['icon'],
                'route' => $nav['route'],
                'permission' => $nav['permission'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
            // create permission
            DB::table('permissions')->insert([
                'name' => $nav['permission'],
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            // add sub menus on database
            foreach ($nav['sub_menu'] as $sub_menu) {
                DB::table('sidebar_navigations')->insert([
                    'name' => $sub_menu['name'],
                    'route' => $sub_menu['route'],
                    'icon'=> $sub_menu['icon'],
                    'parent_id' => $nav_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'permission' => $nav['permission'],
                ]);
            }
        }
        
    }
}

