<?php

namespace Database\Seeders;

use App\Models\HostingPlatform;
use Illuminate\Database\Seeder;

class HostingPlatformSeeder extends Seeder
{
    public function run(): void
    {
        $platforms = [
            [
                'name' => 'Namecheap',
                'value' => 'namecheap',
                'requires_tutorial' => true,
                'tutorial_link' => '#',
                'sort_order' => 1
            ],
            [
                'name' => 'Cloudflare',
                'value' => 'cloudflare',
                'sort_order' => 2
            ],
            [
                'name' => 'GoDaddy',
                'value' => 'godaddy',
                'sort_order' => 3
            ],
            [
                'name' => 'Porkbun',
                'value' => 'porkbun',
                'sort_order' => 4
            ],
            [
                'name' => 'Squarespace',
                'value' => 'squarespace',
                'sort_order' => 5
            ],
            [
                'name' => 'Spaceship',
                'value' => 'spaceship',
                'sort_order' => 6
            ],
            [
                'name' => 'Hostinger',
                'value' => 'hostinger',
                'sort_order' => 7
            ],
            [
                'name' => 'Other',
                'value' => 'other',
                'sort_order' => 99
            ]
        ];

        foreach ($platforms as $platform) {
            HostingPlatform::create($platform);
        }
    }
}
