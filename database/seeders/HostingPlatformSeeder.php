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
                'tutorial_link' => 'https://www.dropbox.com/scl/fi/4qilvns2hdrtlufp5f94l/Guide-to-Get-Backup-Codes-for-Namecheap.paper?rlkey=z10rnjj9wqkow99b3vmjwujnj&dl=0',
                'import_note' => '<strong>IMPORTANT</strong> - Please follow the steps from this document to grant us access to your Namecheap hosting account:',
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
                        'label' => 'Namecheap username (Not email)',
                        'type' => 'text',
                        'required' => true
                    ],
                    'platform_password' => [
                        'label' => 'Namecheap Password',
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
                'tutorial_link' => ' https://www.dropbox.com/scl/fi/tvdnb5a1bq49uxmunlloq/GoDaddy.paper?rlkey=zdb2jyucyus9i6dy6d0404whn&st=agajh1lx&dl=0',
                'import_note' => '<strong>IMPORTANT</strong> - Please follow the steps from this document to grant us access to your GoDaddy hosting account:',
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
                'tutorial_link' => 'https://www.dropbox.com/scl/fi/ervz2j220xncqyfq2nr3k/Porkbun-access.paper?rlkey=phml69sl98yyrmr2tjhqc89ei&st=u50hu1qr&dl=0',
                'import_note' => '<strong>IMPORTANT</strong> - Please follow the steps from this document to grant us access to your Porkbun hosting account:',
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
                'import_note' => '<strong>IMPORTANT</strong> - Please follow the steps from this document to grant us access to your Squarespace hosting account:',
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
                'import_note' => '<strong>IMPORTANT</strong> - Please follow the steps from this document to grant us access to your Spaceship hosting account:',
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
                'import_note' => '<strong>IMPORTANT</strong> - Please follow the steps from this document to grant us access to your Hostinger hosting account:',
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
                'import_note' => '<strong>IMPORTANT</strong> - Please follow the steps from this document to grant us access to your Other hosting account:',
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
                'import_note' => '<strong>IMPORTANT</strong> - Please follow the steps from this document to grant us access to your Cloudflare hosting account:',
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
            HostingPlatform::updateOrCreate(
                ['value' => $platform['value']],
                $platform
            );
        }
    }
}
