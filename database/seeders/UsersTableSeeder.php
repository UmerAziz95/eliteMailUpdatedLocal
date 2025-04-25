<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        $users = [
            [
                'email' => 'super.admin@5dsolutions.ae',
                'name' => 'Super Admin',
                'role_id' => 1,
            ],
            [
                'email' => 'customer@email.com',
                'name' => 'Customer User',
                'role_id' => 3,
            ],
            [
                'email' => 'contractor@email',
                'name' => 'Contractor User',
                'role_id' => 4,
            ],
            [
                'email' => 'mod@email.com',
                'name' => 'Moderator',
                'role_id' => 5,
            ],
            [
                'email' => 'sub-admin@email.com',
                'name' => 'Sub Admin',
                'role_id' => 2,
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['email' => $user['email']], // Unique key
                [
                    'name' => $user['name'],
                    'role_id' => $user['role_id'],
                    'password' => Hash::make('password'), // Always reset password
                    'status' => 1,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}


