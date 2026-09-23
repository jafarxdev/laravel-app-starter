<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DevelopmentUsersSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $accounts = [
            ['Super Admin', 'admin@example.com', 'super-admin'],
            ['Sample Admin', 'sample-admin@example.com', 'admin'],
            ['Sample Manager', 'manager@example.com', 'manager'],
            ['Sample Staff', 'staff@example.com', 'staff'],
            ['Sample Viewer', 'viewer@example.com', 'viewer'],
        ];

        foreach ($accounts as [$name, $email, $role]) {
            $user = User::firstOrCreate(['email' => $email], [
                'name' => $name,
                'password' => 'password',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);

            if ($user->wasRecentlyCreated) {
                $user->forceFill(['email_verified_at' => now()])->save();
                $user->syncRoles([Role::where('slug', $role)->firstOrFail()->id]);
            }
        }
    }
}
