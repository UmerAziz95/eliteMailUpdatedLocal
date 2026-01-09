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
                'api_endpoint' => config('mailin_ai.base_url', 'https://api.mailin.ai'),
                'email' => env('MAILIN_EMAIL', ''),
                'password' => env('MAILIN_PASSWORD', ''),
                'additional_config' => null,
                'split_percentage' => 50.00,
                'priority' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Mailrun',
                'slug' => 'mailrun',
                'api_endpoint' => null,
                'email' => 'dummy@mailrun.com',
                'password' => 'dummy_password',
                'additional_config' => null,
                'split_percentage' => 50.00,
                'priority' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Zapmail',
                'slug' => 'zapmail',
                'api_endpoint' => config('zapmail.base_url', null),
                'email' => env('ZAPMAIL_EMAIL', ''),
                'password' => env('ZAPMAIL_PASSWORD', ''),
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

