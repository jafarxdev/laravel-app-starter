<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('starter.permissions') as $slug) {
            Permission::firstOrCreate(['slug' => $slug], [
                'name' => Str::headline(str_replace('.', ' ', $slug)),
                'module' => Str::before($slug, '.'),
            ]);
        }

        $assignments = [
            'super-admin' => config('starter.permissions'),
            'admin' => config('starter.permissions'),
            'manager' => ['dashboard.view', 'users.view'],
            'staff' => ['dashboard.view'],
            'viewer' => ['dashboard.view'],
        ];

        foreach ($assignments as $slug => $permissions) {
            $role = Role::firstOrCreate(['slug' => $slug], [
                'name' => Str::headline($slug),
                'is_system' => $slug === 'super-admin',
                'description' => 'Initial general-purpose role.',
            ]);

            if ($role->wasRecentlyCreated) {
                $role->permissions()->sync(Permission::whereIn('slug', $permissions)->pluck('id'));
            }
        }
    }
}
