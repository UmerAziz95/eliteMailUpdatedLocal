<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CustomRoleSeeder::class,
            RolesTableSeeder::class,
            HostingPlatformSeeder::class,
            SendingPlatformSeeder::class,
            UsersTableSeeder::class,
            SidebarNavigationSeeder::class,
            StatusesTableSeeder::class,
        ]);
    }
}
