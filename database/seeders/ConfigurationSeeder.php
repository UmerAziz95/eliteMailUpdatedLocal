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
            [
                'key' => 'CHARGEBEE_PUBLISHABLE_API_KEY',
                'value' => 'test_AhIaXMucdYCKah7boupv0BdwxrB3ljcdSk',
                'type' => 'string',
                'description' => 'Chargebee Publishable API Key for payment gateway integration'
            ],
            [
                'key' => 'CHARGEBEE_SITE',
                'value' => 'projectinbox-test',
                'type' => 'string',
                'description' => 'Chargebee Site name/identifier for API authentication'
            ],
            [
                'key' => 'CHARGEBEE_API_KEY',
                'value' => 'test_EFIuXC2fuB1OiUPVy5y6cdMDQN3derDJL',
                'type' => 'string',
                'description' => 'Chargebee Secret API Key for server-side operations'
            ],
            [
                'key' => 'SYSTEM_NAME',
                'value' => 'My Application',
                'type' => 'string',
                'description' => 'Name of the application displayed across the system'
            ],
            [
                'key' => 'ADMIN_EMAIL',
                'value' => 'admin@example.com',
                'type' => 'string',
                'description' => 'Primary administrator email address for system notifications'
            ],
            [
                'key' => 'SUPPORT_EMAIL',
                'value' => 'support@example.com',
                'type' => 'string',
                'description' => 'Support email address for customer inquiries'
            ],
            [
                'key' => 'FOOTER_TEXT',
                'value' => '© 2025 My Application. All rights reserved.',
                'type' => 'string',
                'description' => 'Footer text displayed at the bottom of pages'
            ],
            [
                'key' => 'SYSTEM_LOGO',
                'value' => '',
                'type' => 'string',
                'description' => 'System logo image path'
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
