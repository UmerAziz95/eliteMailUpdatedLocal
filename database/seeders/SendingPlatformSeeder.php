<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SendingPlatformSeeder extends Seeder
{
    public function run(): void
    {
        $platforms = [
            [
                'name' => 'Instantly',
                'value' => 'instantly',
                'fields' => [
                    'sequencer_login' => [
                        'label' => 'Cold email platform - Login',
                        'type' => 'email',
                        'required' => true
                    ],
                    'sequencer_password' => [
                        'label' => 'Cold email platform - Password',
                        'type' => 'password',
                        'required' => true
                    ]
                ]
            ],
            [
                'name' => 'Prospi.ai',
                'value' => 'prospi',
                'fields' => [
                    'sequencer_login' => [
                        'label' => 'Cold email platform - Login',
                        'type' => 'email',
                        'required' => true
                    ],
                    'sequencer_password' => [
                        'label' => 'Cold email platform - Password',
                        'type' => 'password',
                        'required' => true
                    ]
                ]
            ],
            // Smartlead
            [
                'name' => 'Smartlead',
                'value' => 'smartlead',
                'fields' => [
                    'sequencer_login' => [
                        'label' => 'Cold email platform - Login',
                        'type' => 'email',
                        'required' => true
                    ],
                    'sequencer_password' => [
                        'label' => 'Cold email platform - Password',
                        'type' => 'password',
                        'required' => true
                    ]
                ]
            ],
            // Lemlist
            [
                'name' => 'Lemlist',
                'value' => 'lemlist',
                'fields' => [
                    'sequencer_login' => [
                        'label' => 'Cold email platform - Login',
                        'type' => 'email',
                        'required' => true
                    ],
                    'sequencer_password' => [
                        'label' => 'Cold email platform - Password',
                        'type' => 'password',
                        'required' => true
                    ]
                ]
            ],
            // Pipl.ai
            [
                'name' => 'Pipl.ai',
                'value' => 'pipl',
                'fields' => [
                    'sequencer_login' => [
                        'label' => 'Cold email platform - Login',
                        'type' => 'email',
                        'required' => true
                    ],
                    'sequencer_password' => [
                        'label' => 'Cold email platform - Password',
                        'type' => 'password',
                        'required' => true
                    ]
                ]
            ],
            // Reply.io
            [
                'name' => 'Reply.io',
                'value' => 'replyio',
                'fields' => [
                    'sequencer_login' => [
                        'label' => 'Cold email platform - Login',
                        'type' => 'email',
                        'required' => true
                    ],
                    'sequencer_password' => [
                        'label' => 'Cold email platform - Password',
                        'type' => 'password',
                        'required' => true
                    ]
                ]
            ],
            // Hothawk
            [
                'name' => 'Hothawk',
                'value' => 'hothawk',
                'fields' => [
                    'sequencer_login' => [
                        'label' => 'Cold email platform - Login',
                        'type' => 'email',
                        'required' => true
                    ],
                    'sequencer_password' => [
                        'label' => 'Cold email platform - Password',
                        'type' => 'password',
                        'required' => true
                    ]
                ]
            ],
            // Other - Indicated @ "Additional Information" Field Below
            [
                'name' => 'Other',
                'value' => 'other',
                'fields' => [
                    'sequencer_login' => [
                        'label' => 'Cold email platform - Login',
                        'type' => 'email',
                        'required' => true
                    ],
                    'sequencer_password' => [
                        'label' => 'Cold email platform - Password',
                        'type' => 'password',
                        'required' => true
                    ]
                ]
            ],
            [
                'name' => 'Bison',
                'value' => 'bison',
                'fields' => [
                    'bison_url' => [
                        'label' => 'Cold email platform - Your Unique Bison URL',
                        'type' => 'url',
                        'required' => true
                    ],
                    'bison_workspace' => [
                        'label' => 'Cold email platform - Bison Workspace Name',
                        'type' => 'text',
                        'required' => true
                    ],
                    'sequencer_login' => [
                        'label' => 'Cold email platform - Login',
                        'type' => 'email',
                        'required' => true
                    ],
                    'sequencer_password' => [
                        'label' => 'Cold email platform - Password',
                        'type' => 'password',
                        'required' => true
                    ]
                ]
            ]
        ];

        foreach ($platforms as $platform) {
            DB::table('sending_platforms')->updateOrInsert(
                ['value' => $platform['value']],
                [
                    'name' => $platform['name'],
                    'fields' => json_encode($platform['fields']),
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }
    }
}