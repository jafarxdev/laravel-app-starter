<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('database permissions combine across multiple roles', function () {
    $user = User::factory()->create();
    $roles = Role::factory()->count(2)->create();
    $permission = Permission::factory()->create(['slug' => 'users.view', 'module' => 'users']);
    $roles[1]->permissions()->attach($permission);
    $user->syncRoles($roles->modelKeys());

    expect($user->hasRole($roles[0]->slug))->toBeTrue()
        ->and($user->hasAnyRole(['missing', $roles[1]->slug]))->toBeTrue()
        ->and($user->hasPermission('users.view'))->toBeTrue()
        ->and($user->hasAnyPermission(['missing.view', 'users.view']))->toBeTrue()
        ->and(Gate::forUser($user)->allows('users.view'))->toBeTrue()
        ->and(Gate::forUser($user)->allows('users.delete'))->toBeFalse();
});

test('role changes invalidate the users loaded permissions', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create();
    $permission = Permission::factory()->create(['slug' => 'users.view', 'module' => 'users']);
    $role->permissions()->attach($permission);
    $user->syncRoles([$role->id]);
    expect($user->hasPermission('users.view'))->toBeTrue();

    $user->syncRoles([]);

    expect(Gate::forUser($user)->allows('users.view'))->toBeFalse();
});

test('super admin has new permissions but cannot bypass self deletion policy', function () {
    $user = User::factory()->create();
    $user->syncRoles([Role::factory()->superAdmin()->create()->id]);

    expect(Gate::forUser($user)->allows('future-module.create'))->toBeTrue()
        ->and(Gate::forUser($user)->allows('delete', $user))->toBeFalse();
});

test('inactive super admin has no permissions', function () {
    $user = User::factory()->inactive()->create();
    $user->syncRoles([Role::factory()->superAdmin()->create()->id]);

    expect(Gate::forUser($user)->allows('users.view'))->toBeFalse();
});

test('ordinary administrators cannot edit super admin accounts', function () {
    $actor = User::factory()->create();
    $role = Role::factory()->create();
    $role->permissions()->attach(Permission::factory()->create(['slug' => 'users.update', 'module' => 'users']));
    $actor->syncRoles([$role->id]);
    $super = User::factory()->create();
    $super->syncRoles([Role::factory()->superAdmin()->create()->id]);

    expect(Gate::forUser($actor)->allows('update', $super))->toBeFalse();
});
