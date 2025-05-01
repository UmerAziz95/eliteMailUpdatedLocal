<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User; // Import User model
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or get permissions
        $editArticles = Permission::firstOrCreate(['name' => 'edit articles', 'guard_name' => 'web']);
        $deleteArticles = Permission::firstOrCreate(['name' => 'delete articles', 'guard_name' => 'web']);

        // Create or get role
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        // Assign permissions to role
        $adminRole->givePermissionTo([$editArticles, $deleteArticles]);

        // Assign role to user
        $user = User::find(1);
        if ($user && !$user->hasRole($adminRole->name)) {
            $user->assignRole($adminRole);
        }
    }
}
