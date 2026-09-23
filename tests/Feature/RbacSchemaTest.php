<?php

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

test('a user cannot receive the same role twice', function () {
    $user = User::factory()->create();
    $roleId = DB::table('roles')->insertGetId(['name' => 'Reviewer', 'slug' => 'reviewer']);
    DB::table('role_user')->insert(['user_id' => $user->id, 'role_id' => $roleId]);

    expect(fn () => DB::table('role_user')->insert(['user_id' => $user->id, 'role_id' => $roleId]))
        ->toThrow(QueryException::class);
});

test('removing a role preserves its users and permissions', function () {
    $user = User::factory()->create();
    $roleId = DB::table('roles')->insertGetId(['name' => 'Reviewer', 'slug' => 'reviewer']);
    $permissionId = DB::table('permissions')->insertGetId(['name' => 'View', 'slug' => 'records.view', 'module' => 'records']);
    DB::table('role_user')->insert(['user_id' => $user->id, 'role_id' => $roleId]);
    DB::table('permission_role')->insert(['permission_id' => $permissionId, 'role_id' => $roleId]);

    DB::table('roles')->where('id', $roleId)->delete();

    $this->assertModelExists($user);
    $this->assertDatabaseHas('permissions', ['id' => $permissionId]);
    $this->assertDatabaseEmpty('role_user');
    $this->assertDatabaseEmpty('permission_role');
});
