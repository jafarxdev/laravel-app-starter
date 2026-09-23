<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;

test('seeding twice preserves accounts passwords and customized role permissions', function () {
    $this->seed(DatabaseSeeder::class);
    $admin = User::where('email', 'admin@example.com')->firstOrFail();
    $admin->update(['password' => 'changed-password']);
    $manager = Role::where('slug', 'manager')->firstOrFail();
    $manager->permissions()->detach();

    $this->seed(DatabaseSeeder::class);

    $this->assertDatabaseCount('users', 5);
    $this->assertDatabaseCount('roles', 5);
    $this->assertDatabaseCount('permissions', 18);
    expect(Hash::check('changed-password', $admin->fresh()->password))->toBeTrue()
        ->and($manager->permissions()->count())->toBe(0)
        ->and($admin->hasRole('super-admin'))->toBeTrue();
});

test('development users are not seeded in production', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    $this->assertDatabaseEmpty('users');
    $this->assertDatabaseCount('roles', 5);
});
