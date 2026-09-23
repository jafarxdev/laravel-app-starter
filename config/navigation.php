<?php

return [
    ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => 'dashboard', 'permission' => 'dashboard.view'],
    ['label' => 'Users', 'route' => 'users.index', 'active' => 'users.*', 'permission' => 'users.view'],
    ['label' => 'Roles', 'route' => 'roles.index', 'active' => 'roles.*', 'permission' => 'roles.view'],
    ['label' => 'Permissions', 'route' => 'permissions.index', 'active' => 'permissions.*', 'permission' => 'permissions.view'],
    ['label' => 'General settings', 'route' => 'general-settings', 'active' => 'general-settings', 'permission' => 'settings.view'],
    ['label' => 'Audit log', 'route' => 'audit-logs', 'active' => 'audit-logs', 'permission' => 'audit-logs.view'],
];
