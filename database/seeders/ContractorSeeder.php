<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ContractorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test contractors if none exist
        $contractorsData = [
            [
                'name' => 'John Contractor',
                'email' => 'john.contractor@example.com',
                'password' => Hash::make('password'),
                'role_id' => 4,
                'status' => 1
            ],
            [
                'name' => 'Sarah Builder',
                'email' => 'sarah.builder@example.com',
                'password' => Hash::make('password'),
                'role_id' => 4,
                'status' => 1
            ],
            [
                'name' => 'Mike Worker',
                'email' => 'mike.worker@example.com',
                'password' => Hash::make('password'),
                'role_id' => 4,
                'status' => 1
            ]
        ];

        foreach ($contractorsData as $contractorData) {
            // Only create if doesn't exist
            User::firstOrCreate(
                ['email' => $contractorData['email']],
                $contractorData
            );
        }

        $this->command->info('Contractor users seeded successfully!');
    }
}