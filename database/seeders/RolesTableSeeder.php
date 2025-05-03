<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            ['id' => 1, 'name' => 'Admin'],
            ['id' => 2, 'name' => 'Sub-Admin'],
            ['id' => 3, 'name' => 'Customer'],
            ['id' => 4, 'name' => 'Contractor'],
            ['id' => 5, 'name' => 'Mod'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['id' => $role['id']],
                [
                    'name' => $role['name'],
                    'guard_name' => 'web',
                ]
            );
        }
    }
}
