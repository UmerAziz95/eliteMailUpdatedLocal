<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\GhlSetting;

class GhlSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if settings already exist
        if (GhlSetting::count() > 0) {
            $this->command->info('GHL settings already exist. Skipping seeder.');
            return;
        }

        // Create initial settings from .env values
        GhlSetting::create([
            'enabled' => env('GHL_ENABLED', false),
            'base_url' => env('GHL_BASE_URL', 'https://rest.gohighlevel.com/v1'),
            'api_token' => env('GHL_API_TOKEN', ''),
            'location_id' => env('GHL_LOCATION_ID', ''),
            'auth_type' => env('GHL_AUTH_TYPE', 'bearer'),
            'api_version' => env('GHL_API_VERSION', '2021-07-28'),
        ]);

        $this->command->info('GHL settings seeded successfully!');
    }
}
