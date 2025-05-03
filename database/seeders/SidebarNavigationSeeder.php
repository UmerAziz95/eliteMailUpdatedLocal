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
                'icon' => 'fa fa-dashboard',
                'route' => 'admin.dashboard',
                'sub_menu' => [],
                'permission' => 'Dashboard'
            ],
            [
                'name' => 'Admins',
                'icon' => 'fa fa-users',
                'route' => 'admin.index',
                'permission' => 'Admins',
                'sub_menu' => [
                 
                ]
            ],
            [
                'name' => 'Customer',
                'icon' => 'fa fa-users',
                'route' => 'admin.customer',
                'permission' => 'Customer',
                'sub_menu' => [
                 
                ]
            ],
            [
                'name' => 'Subscriptions',
                'icon' => 'fa fa-users',
                'route' => 'admin.subs.view',
                'permission' => 'Subscriptions',
                'sub_menu' => [
                 
                ]
            ],
         
            [
                'name' => 'Contractors',
                'icon' => 'fa fa-money-check-dollar',
                'route' => 'admin.contractorList',
                // Payments
                'permission' => 'Contractors',
                'sub_menu' => [
                 
                ]
            ],
            [
                'name' => 'Invoices',
                'icon' => 'fa fa-money-check-dollar',
                'route' => 'admin.invoices.index',
                // Payments
                'permission' => 'Invoices',
                'sub_menu' => [
                 
                ]
            ],
            [
                'name' => 'Orders',
                'icon' => 'fa fa-money-check-dollar',
                'route' => 'admin.orders',
                // Payments
                'permission' => 'Orders',
                'sub_menu' => [
                 
                ]
            ],
            [
                'name' => 'Payments',
                'icon' => 'fa fa-money-check-dollar',
                'route' => 'admin.payments',
                // Payments
                'permission' => 'Payments',
                'sub_menu' => [
                 
                ]
            ],
         
            // admin.setting
            [
                'name' => 'Settings',
                'icon' => 'fa fa-cogs',
                'route' => 'admin.setting',
                // Settings
                'permission' => 'Settings',
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

