<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SmtpProviderSplit;

class SmtpProviderSplitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $providers = [
            [
                'name' => 'Mailin',
                'slug' => 'mailin',
                'api_endpoint' => 'https://api.mailin.ai',
                'email' => '',
                'password' => '',
                'additional_config' => null,
                'split_percentage' => 100.00,
                'priority' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Mailrun',
                'slug' => 'mailrun',
                'api_endpoint' => 'https://api.mailrun.ai/api',
                'email' => '',
                'password' => '',
                'api_secret' => null,
                'additional_config' => null,
                'split_percentage' => 0.00,
                'priority' => 2,
                'is_active' => false,
            ],
            [
                'name' => 'Premiuminboxes',
                'slug' => 'premiuminboxes',
                'api_endpoint' => 'https://api.piwhitelabel.dev/api/v1',
                'email' => '',
                'password' => '',
                'api_secret' => null,
                'additional_config' => null,
                'split_percentage' => 0.00,
                'priority' => 3,
                'is_active' => false,
            ],
        ];

        foreach ($providers as $provider) {
            SmtpProviderSplit::updateOrCreate(
                ['slug' => $provider['slug']],
                $provider
            );
        }
    }
}

