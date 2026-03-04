<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pool Order Editable Statuses
    |--------------------------------------------------------------------------
    |
    | Define which statuses allow pool orders to be edited.
    | When a pool order has one of these statuses, users with appropriate
    | permissions can edit the order configuration and domains.
    |
    | Available statuses: 'draft', 'pending', 'in-progress', 'completed', 'cancelled'
    |
    */

    'editable_statuses' => [
        'draft',        // New orders start as draft
        'pending',
        'in-progress',  // Uncomment to allow editing in-progress orders
        'completed',    // Uncomment to allow editing completed orders
    ],

    /*
    |--------------------------------------------------------------------------
    | Pool Order Roles with Edit Permission
    |--------------------------------------------------------------------------
    |
    | Define which user roles can edit pool orders.
    | Role IDs: 1 = Admin, 2 = Sub-Admin, 3 = Customer, 4 = Contractor
    |
    */

    'editable_roles' => [
        1, // Admin
        2, // Sub-Admin
        4, // Contractor
        3, // Customer
    ],

];
