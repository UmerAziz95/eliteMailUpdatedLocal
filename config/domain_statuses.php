<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Domain Status Configuration
    |--------------------------------------------------------------------------
    |
    | Define all available domain statuses with their display properties
    | including colors, background colors, and labels.
    |
    */

    'statuses' => [
        'warming' => [
            'label' => 'Warming',
            'color' => '#856404',
            'bg_color' => '#fff3cd',
            'badge_class' => 'warning',
            'icon' => 'ti-flame',
            'description' => 'Domain is in warming phase'
        ],
        'available' => [
            'label' => 'Available',
            'color' => '#155724',
            'bg_color' => '#d4edda',
            'badge_class' => 'success',
            'icon' => 'ti-check-circle',
            'description' => 'Domain is available for assignment'
        ],
        'in-progress' => [
            'label' => 'In Progress',
            'color' => '#004085',
            'bg_color' => '#cce5ff',
            'badge_class' => 'primary',
            'icon' => 'ti-clock',
            'description' => 'Domain is assigned to an active order'
        ],
        'used' => [
            'label' => 'Used',
            'color' => '#721c24',
            'bg_color' => '#f8d7da',
            'badge_class' => 'danger',
            'icon' => 'ti-lock',
            'description' => 'Domain is currently in use'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Status
    |--------------------------------------------------------------------------
    |
    | The default status for new domains
    |
    */

    'default' => 'warming',

    /*
    |--------------------------------------------------------------------------
    | Editable Statuses
    |--------------------------------------------------------------------------
    |
    | Statuses that can be manually edited by admins
    |
    */

    'editable' => [
        'warming',
        'available',
        'in-progress',
    ],

    /*
    |--------------------------------------------------------------------------
    | Status Transitions
    |--------------------------------------------------------------------------
    |
    | Define allowed status transitions
    |
    */

    'transitions' => [
        'warming' => ['available', 'in-progress'],
        'available' => ['warming', 'in-progress', 'used'],
        'in-progress' => ['available', 'used'],
        'used' => ['available'],
    ],

];
