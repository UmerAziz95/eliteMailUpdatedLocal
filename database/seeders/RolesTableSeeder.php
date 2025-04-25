<?php

// database/seeders/RolesTableSeeder.php
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'Admin',      'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Editor',     'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Customer',   'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Contractor', 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Mod',        'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
