<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AssignRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Step 1: Create or update user
        $user = User::updateOrCreate(
            ['email' => 'super.admin@app.projectinbox.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('proinbox@Admin'),
            ]
        );

        // Step 2: Create or get role
        $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        // Step 3: Get all permissions except 'Mod'
        $permissions = Permission::whereNotIn('name', ['Mod'])->get();

        // Step 4: Assign all permissions to the role
        $role->syncPermissions($permissions);

        // Step 5: Assign role to user
        $user->assignRole($role);
    }
}

