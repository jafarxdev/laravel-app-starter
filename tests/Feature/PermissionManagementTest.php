<?php

use App\Models\Permission;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Livewire\Volt\Volt;

function signInForPermissionManagement(): void
{
    test()->seed(DatabaseSeeder::class);
    test()->actingAs(User::where('email', 'admin@example.com')->firstOrFail());
}

test('custom permission can be created updated filtered and deleted', function () {
    signInForPermissionManagement();

    Volt::test('permissions.index')->call('create')->set('name', 'View records')
        ->set('module', 'records')->set('slug', 'records.view')->call('save')->assertHasNoErrors();
    $permission = Permission::where('slug', 'records.view')->firstOrFail();
    Volt::test('permissions.index')->set('moduleFilter', 'records')->assertSee('records.view')->assertDontSee('users.create')
        ->call('edit', $permission->id)->set('name', 'Read records')->call('save')->assertHasNoErrors();
    expect($permission->fresh()->name)->toBe('Read records');
    Volt::test('permissions.index')->call('confirmDelete', $permission->id)->call('delete')->assertHasNoErrors();

    $this->assertModelMissing($permission);
});

test('invalid duplicated and mismatched slugs are rejected', function (string $slug) {
    signInForPermissionManagement();

    Volt::test('permissions.index')->call('create')->set('name', 'Invalid')
        ->set('module', 'records')->set('slug', $slug)->call('save')->assertHasErrors('slug');

    $this->assertDatabaseCount('permissions', 18);
})->with(['users.view', 'records', 'records.view.extra', 'Records.view', 'other.view']);

test('critical permissions cannot be edited or deleted even by super admin', function () {
    signInForPermissionManagement();
    $permission = Permission::where('slug', 'roles.assign-permissions')->firstOrFail();

    Volt::test('permissions.index')->call('edit', $permission->id)->assertForbidden();
    Volt::test('permissions.index')->call('confirmDelete', $permission->id)->assertForbidden();

    $this->assertModelExists($permission);
});

test('users without permission cannot access permission management', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('permissions.index'))->assertForbidden();
    Volt::test('permissions.index')->assertForbidden();
});
