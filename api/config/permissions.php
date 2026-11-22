<?php

/*
|--------------------------------------------------------------------------
| Permissions
|--------------------------------------------------------------------------
|
| The only place permissions are declared: module => [action => label]. The
| name is "<module>.<action>". Apply with `php artisan permissions:sync`
| (module seeders run it too). Every admin route must be guarded by one.
|
*/

return [

    'modules' => [
        'dashboard' => [
            'view' => 'View the dashboard',
        ],
        'users' => [
            'view' => 'View users and their sessions',
            'create' => 'Create users',
            'update' => 'Edit, suspend and sign out users',
            'delete' => 'Delete users',
        ],
        'roles' => [
            'view' => 'View roles and permissions',
            'create' => 'Create roles',
            'update' => 'Edit roles and their permissions',
            'delete' => 'Delete roles',
        ],
        'menus' => [
            'view' => 'View the menu tree',
            'create' => 'Add custom menu entries',
            'update' => 'Edit and reorder menus',
            'delete' => 'Delete custom menu entries',
        ],
        'settings' => [
            'view' => 'View settings',
            'update' => 'Edit settings',
        ],
        'audit' => [
            'view' => 'View the audit log',
        ],
        // <make:module permissions> new modules are added above this line
    ],

    /*
    | Role => permissions granted when seeding (additive, never revokes). Access
    | management is not granted to `admin`: only the superadmin manages it until
    | a role is given those permissions.
    */
    'grants' => [
        'admin' => ['dashboard.view', 'settings.view', 'settings.update'],
    ],

    /* Seeded roles that cannot be renamed or deleted (the superadmin role is always protected). */
    'system_roles' => ['admin'],

];
