<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Livewire\Volt\Volt;

function signInForRoleManagement(): void
{
    test()->seed(DatabaseSeeder::class);
    test()->actingAs(User::where('email', 'admin@example.com')->firstOrFail());
}

test('role can be created and permissions assigned', function () {
    signInForRoleManagement();
    $permission = Permission::where('slug', 'users.view')->firstOrFail();

    Volt::test('roles.index')->call('create')->set('name', 'Review team')->set('slug', 'review-team')
        ->set('selectedPermissions', [$permission->id])->call('save')->assertHasNoErrors();

    $role = Role::where('slug', 'review-team')->firstOrFail();
    expect($role->permissions()->pluck('slug')->all())->toBe(['users.view']);
});

test('duplicate and reserved slugs are rejected', function (string $slug) {
    signInForRoleManagement();

    Volt::test('roles.index')->call('create')->set('name', 'Duplicate')->set('slug', $slug)
        ->call('save')->assertHasErrors('slug');

    $this->assertDatabaseCount('roles', 5);
})->with(['admin', 'super-admin', 'Invalid Slug']);

test('system role cannot be edited or deleted', function () {
    signInForRoleManagement();
    $role = Role::where('slug', 'super-admin')->firstOrFail();

    Volt::test('roles.index')->call('edit', $role->id)->assertForbidden();
    Volt::test('roles.index')->call('confirmDelete', $role->id)->assertForbidden();

    $this->assertModelExists($role);
});

test('deleting assigned role requires exact confirmation and preserves users', function () {
    signInForRoleManagement();
    $role = Role::where('slug', 'staff')->firstOrFail();
    $user = User::where('email', 'staff@example.com')->firstOrFail();

    Volt::test('roles.index')->call('confirmDelete', $role->id)
        ->call('delete')->assertHasErrors('confirmation')
        ->set('confirmation', 'staff')->call('delete')->assertHasNoErrors();

    $this->assertModelMissing($role);
    $this->assertModelExists($user);
    expect($user->roles()->count())->toBe(0);
});

test('unauthorized users cannot manage roles', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('roles.index'))->assertForbidden();
    Volt::test('roles.index')->assertForbidden();
});

test('administrator cannot remove permissions from own assigned role', function () {
    signInForRoleManagement();
    $actor = User::where('email', 'sample-admin@example.com')->firstOrFail();
    $role = Role::where('slug', 'admin')->firstOrFail();
    $this->actingAs($actor);

    Volt::test('roles.index')->call('edit', $role->id)->set('selectedPermissions', [])
        ->call('save')->assertForbidden();

    expect($role->permissions()->count())->toBe(18);
});
