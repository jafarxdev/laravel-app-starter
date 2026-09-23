<?php

return [
    'locales' => ['en' => 'English', 'fa' => 'فارسی / دری'],

    'permissions' => [
        'dashboard.view',
        'users.view', 'users.create', 'users.update', 'users.delete', 'users.assign-roles',
        'roles.view', 'roles.create', 'roles.update', 'roles.delete', 'roles.assign-permissions',
        'permissions.view', 'permissions.create', 'permissions.update', 'permissions.delete',
        'settings.view', 'settings.update',
        'audit-logs.view',
    ],
];
