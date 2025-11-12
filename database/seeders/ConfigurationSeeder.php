<?php

namespace Database\Seeders;

use App\Models\Configuration;
use Illuminate\Database\Seeder;

class ConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configurations = [
            [
                'key' => 'PANEL_CAPACITY',
                'value' => '1790',
                'type' => 'number',
                'description' => 'Maximum capacity for panel assignments (total inboxes per panel)'
            ],
            [
                'key' => 'MAX_SPLIT_CAPACITY',
                'value' => '1790',
                'type' => 'number',
                'description' => 'Maximum split capacity for dividing panels across multiple contractors'
            ],
            [
                'key' => 'ENABLE_MAX_SPLIT_CAPACITY',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Enable or disable the maximum split capacity feature for panel management'
            ],
            [
                'key' => 'PLAN_FLAT_QUANTITY',
                'value' => '99',
                'type' => 'number',
                'description' => 'Default flat quantity value used for plan calculations and billing'
            ],
            [
                'key' => 'PROVIDER_TYPE',
                'value' => 'Google',
                'type' => 'string',
                'description' => 'Email provider type for Panel Allocation i.e. on which panel new orders will be placed'
            ],
            [
                'key' => 'MICROSOFT_365_CAPACITY',
                'value' => '300',
                'type' => 'number',
                'description' => 'Maximum capacity for Microsoft 365 panel assignments'
            ],
        ];

        foreach ($configurations as $config) {
            Configuration::updateOrCreate(
                ['key' => $config['key']],
                $config
            );
        }
    }
}
