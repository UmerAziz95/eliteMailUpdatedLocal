<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            ['id' => 1, 'name' => 'Admin',      'status' => 1],
            ['id' => 2, 'name' => 'Editor',     'status' => 1],
            ['id' => 3, 'name' => 'Customer',   'status' => 1],
            ['id' => 4, 'name' => 'Contractor', 'status' => 1],
            ['id' => 5, 'name' => 'Mod',        'status' => 1],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['id' => $role['id']],
                [
                    'name' => $role['name'],
                    'status' => $role['status'],
                    'updated_at' => now(),
                    'created_at' => now()
                ]
            );
        }
    }
}

