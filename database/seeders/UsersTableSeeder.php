<?php

// database/seeders/UsersTableSeeder.php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->insert([
            [
                'name' => 'Super Admin',
                'email' => 'super.admin@5dsolutions.ae',
                'password' => Hash::make('Admin123#'), // Use a strong password in production
                'role_id' => 1, // Admin
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Customer User',
                'email' => 'customer@email.com',
                'password' => Hash::make('Admin123#'),
                'role_id' => 3, // Customer
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Contractor User',
                'email' => 'contractor@email',
                'password' => Hash::make('Admin123#'),
                'role_id' => 4, // Contractor
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Moderator',
                'email' => 'mod@email.com',
                'password' => Hash::make('Admin123#'),
                'role_id' => 5, // Mod
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sub Admin',
                'email' => 'sub-admin@email.com',
                'password' => Hash::make('Admin123#'),
                'role_id' => 2, // Editor
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

