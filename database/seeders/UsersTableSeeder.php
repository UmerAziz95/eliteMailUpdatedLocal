<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;


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
                'email' => 'contractor@email.com',
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
                    'password' => Hash::make('Admin123#'), // Always reset password
                    'status' => 1,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

            $user = User::where('email', 'super.admin@5dsolutions.ae')->first();

            if ($user) {
                $user->update([
                    'name' => 'Super Admin',
                    'password' => Hash::make('Admin123#'), // Update password if needed
                ]);
            } else {
                $user = User::create([
                    'name' => 'Super Admin',
                    'email' => 'super.admin@5dsolutions.ae',
                    'password' => Hash::make('Admin123#'),
                    'role_id' => 1,
                ]);
            }

            // Step 2: Create or update role 
            $role = Role::firstOrCreate(
                ['name' => 'super-admin', 'guard_name' => 'web']
            );

            // Step 3: Get all permissions except "Mod"
            $permissions = Permission::where('name', '!=', 'Mod')->get();

            // Step 4: Sync permissions to role
            $role->syncPermissions($permissions);

            // Step 5: Assign role to user (avoid duplicate assignment)
            if (!$user->hasRole($role->name)) {
                $user->assignRole($role);
            }
 
    }
}


