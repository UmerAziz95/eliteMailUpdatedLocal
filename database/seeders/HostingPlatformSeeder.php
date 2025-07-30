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
                'tutorial_link' => 'https://docs.google.com/document/d/1D98lxHwJtAlw2MBIIHsCuejvARO2Ge25Z-ZLr91Aqm8/edit?tab=t.0#heading=h.gwg31lb1l0ct',
                'fields' => [
                    'access_tutorial' => [
                        'label' => 'Domain Hosting Platform - Namecheap - Access Tutorial',
                        'type' => 'select',
                        'options' => [
                            'yes' => "Yes - I reviewed the tutorial and am submitting the access information in requested format.",
                            'no' => "No - I haven't reviewed the tutorial, and understand that incorrect submission might delay the delivery.",
                        ],
                        'required' => true
                    ],
                    'backup_codes' => [
                        'label' => 'Domain Hosting Platform - Namecheap - Backup Codes',
                        'type' => 'textarea',
                        'required' => true
                    ],
                    'platform_login' => [
                        'label' => 'Domain Hosting Platform - Login',
                        'type' => 'text',
                        'required' => true
                    ],
                    'platform_password' => [
                        'label' => 'Domain Hosting Platform - Password',
                        'type' => 'password',
                        'required' => true
                    ]
                ],
                'sort_order' => 1
            ],
            [
                'name' => 'GoDaddy',
                'value' => 'godaddy',
                'requires_tutorial' => true,
                'tutorial_link' => '#',
                'fields' => [
                    'access_tutorial' => [
                        'label' => 'Domain Hosting Platform - GoDaddy - Access Tutorial',
                        'type' => 'select',
                        'options' => [
                            'yes' => "Yes - I sent DELEGATE ACCESS to hello@projectinbox.ai and entered my GoDaddy Account Name (NOT email) below.",
                            'no' => "No - I haven't reviewed the tutorial, and understand that incorrect submission might delay the delivery.",
                        ],
                        'required' => true
                    ],
                    // 'account_name' => [
                    //     'label' => 'Domain Hosting Platform - Your GoDaddy Account Name (NOT Email)',
                    //     'type' => 'text',
                    //     'required' => true
                    // ],
                    // hosting_platform
                    // 'hosting_platform'=>[
                    //     'label' => 'Domain Hosting Platform - GoDaddy - Access Tutorial',
                    //     'type' => 'text',
                    //     'required' => true
                    // ],
                    'platform_login' => [
                        'label' => 'Domain Hosting Platform - Your GoDaddy Account Name (NOT Email)',
                        'type' => 'text',
                        'required' => true
                    ],
                    // 'platform_password' => [
                    //     'label' => 'Domain Hosting Platform - Password',
                    //     'type' => 'password',
                    //     'required' => true
                    // ]
                ],
                'sort_order' => 2
            ],
            [
                'name' => 'Porkbun',
                'value' => 'porkbun',
                'requires_tutorial' => true,
                'tutorial_link' => '#',
                'fields' => [
                    'access_tutorial' => [
                        'label' => 'Domain Hosting Platform - Porkbun - Access Tutorial',
                        'type' => 'select',
                        'options' => [
                            'yes' => "Yes - I disabled all 3 2FAs AND the 'Unrecognized Device 2FA'",
                            'no' => "No - I haven't reviewed the tutorial, and understand that incorrect submission might delay the delivery.",
                        ],
                        'required' => true
                    ],
                    'platform_login' => [
                        'label' => 'Domain Hosting Platform - Login',
                        'type' => 'text',
                        'required' => true
                    ],
                    'platform_password' => [
                        'label' => 'Domain Hosting Platform - Password',
                        'type' => 'password',
                        'required' => true
                    ]
                ],
                'sort_order' => 3
            ],
            [
                'name' => 'Squarespace',
                'value' => 'squarespace',
                'fields' => [
                    'platform_login' => [
                        'label' => 'Domain Hosting Platform - Login',
                        'type' => 'text',
                        'required' => true
                    ],
                    'platform_password' => [
                        'label' => 'Domain Hosting Platform - Password',
                        'type' => 'password',
                        'required' => true
                    ]
                ],
                'sort_order' => 4
            ],
            [
                'name' => 'Spaceship',
                'value' => 'spaceship',
                'fields' => [
                    'platform_login' => [
                        'label' => 'Domain Hosting Platform - Login',
                        'type' => 'text',
                        'required' => true
                    ],
                    'platform_password' => [
                        'label' => 'Domain Hosting Platform - Password',
                        'type' => 'password',
                        'required' => true
                    ]
                ],
                'sort_order' => 5
            ],
            [
                'name' => 'Hostinger',
                'value' => 'hostinger',
                'fields' => [
                    'platform_login' => [
                        'label' => 'Domain Hosting Platform - Login',
                        'type' => 'text',
                        'required' => true
                    ],
                    'platform_password' => [
                        'label' => 'Domain Hosting Platform - Password',
                        'type' => 'password',
                        'required' => true
                    ]
                ],
                'sort_order' => 6
            ],
            [
                'name' => 'Other (Fill in further details in the ‘Additional Details’ field below)',
                'value' => 'other',
                'fields' => [
                    'other_platform' => [
                        'label' => 'Please specify your hosting platform',
                        'type' => 'text',
                        'required' => true
                    ],
                    'platform_login' => [
                        'label' => 'Domain Hosting Platform - Login',
                        'type' => 'text',
                        'required' => true
                    ],
                    'platform_password' => [
                        'label' => 'Domain Hosting Platform - Password',
                        'type' => 'password',
                        'required' => true
                    ]
                ],
                'sort_order' => 99
            ],
            // Cloudflare is not included in the seeder as per the request
            [
                'name' => 'Cloudflare',
                'value' => 'cloudflare',
                'fields' => [
                    'platform_login' => [
                        'label' => 'Domain Hosting Platform - Login',
                        'type' => 'text',
                        'required' => true
                    ],
                    'platform_password' => [
                        'label' => 'Domain Hosting Platform - Password',
                        'type' => 'password',
                        'required' => true
                    ]
                ],
                'sort_order' => 7
            ]
        ];
        HostingPlatform::truncate();
        foreach ($platforms as $platform) {
            HostingPlatform::create($platform);
        }
    }
}
