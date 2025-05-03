<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomRoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['id' => 1, 'name' => 'Admin'],
            ['id' => 2, 'name' => 'Sub-Admin'],
            ['id' => 3, 'name' => 'Customer'],
            ['id' => 4, 'name' => 'Contractor'],
            ['id' => 5, 'name' => 'Mod'],
        ];

        $now = Carbon::now();

        foreach ($roles as $role) {
            DB::table('custom_roles')->updateOrInsert(
                ['id' => $role['id']],
                [
                    'name' => $role['name'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
