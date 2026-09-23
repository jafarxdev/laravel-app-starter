<?php

use App\Actions\ProtectUserAccess;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Volt\Volt;

function userWithPermissions(array $slugs): User
{
    $role = Role::factory()->create();
    foreach ($slugs as $slug) {
        $role->permissions()->attach(Permission::firstOrCreate(['slug' => $slug], [
            'name' => $slug, 'module' => strstr($slug, '.', true),
        ]));
    }
    $user = User::factory()->create();
    $user->syncRoles([$role->id]);

    return $user;
}

test('view permission does not grant direct write actions', function (string $component, string $permission) {
    $this->actingAs(userWithPermissions([$permission]));

    Volt::test($component)->call('create')->assertForbidden();
})->with([['roles.index', 'roles.view'], ['permissions.index', 'permissions.view']]);

test('creating users does not implicitly allow role assignment', function () {
    $actor = userWithPermissions(['users.create', 'users.view']);
    $role = Role::factory()->create();
    $this->actingAs($actor);

    Volt::test('users.form')->set('name', 'Denied')->set('email', 'denied@example.com')
        ->set('password', 'password')->set('password_confirmation', 'password')
        ->set('selectedRoles', [$role->id])->call('save')->assertForbidden();

    $this->assertDatabaseMissing('users', ['email' => 'denied@example.com']);
});

test('creating roles does not implicitly allow permission assignment', function () {
    $actor = userWithPermissions(['roles.create', 'roles.view', 'users.view']);
    $this->actingAs($actor);

    Volt::test('roles.index')->call('create')->set('name', 'Denied')->set('slug', 'denied')
        ->set('selectedPermissions', [Permission::where('slug', 'users.view')->firstOrFail()->id])
        ->call('save')->assertForbidden();

    $this->assertDatabaseMissing('roles', ['slug' => 'denied']);
});

test('role assignment cannot grant abilities beyond the actors access', function () {
    $actor = userWithPermissions(['roles.view', 'roles.create', 'roles.assign-permissions']);
    $permission = Permission::factory()->create(['slug' => 'records.delete', 'module' => 'records']);
    $this->actingAs($actor);

    Volt::test('roles.index')->call('create')->set('name', 'Escalation')->set('slug', 'escalation')
        ->set('selectedPermissions', [$permission->id])->call('save')->assertForbidden();

    $this->assertDatabaseMissing('roles', ['slug' => 'escalation']);
});

test('users cannot reset passwords of accounts with greater access', function () {
    $actor = userWithPermissions(['users.view', 'users.update']);
    $target = userWithPermissions(['settings.update']);
    $this->actingAs($actor);

    $this->get(route('users.edit', $target))->assertForbidden();
    Volt::test('users.form', ['user' => $target])->assertForbidden();
    expect(Gate::forUser($actor)->allows('update', $target))->toBeFalse();
});

test('locked user identifiers cannot be replaced by client input', function () {
    $this->seed(DatabaseSeeder::class);
    $this->actingAs(User::where('email', 'admin@example.com')->firstOrFail());
    $target = User::where('email', 'staff@example.com')->firstOrFail();

    expect(fn () => Volt::test('users.form', ['user' => $target])->set('userId', 999))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

test('revoked delete permission cannot be bypassed with an open confirmation modal', function () {
    $actor = userWithPermissions(['users.view', 'users.delete']);
    $target = User::factory()->create();
    $this->actingAs($actor);
    $component = Volt::test('users.index')->call('confirmDelete', $target->id);
    $actor->syncRoles([]);

    $component->call('delete')->assertForbidden();

    $this->assertModelExists($target);
});

test('last active super admin is protected even if another super admin is inactive', function (string $operation) {
    $this->seed(DatabaseSeeder::class);
    $super = User::where('email', 'admin@example.com')->firstOrFail();
    $inactive = User::factory()->inactive()->create();
    $role = Role::where('slug', 'super-admin')->firstOrFail();
    $inactive->syncRoles([$role->id]);

    expect(fn () => DB::transaction(fn () => (new ProtectUserAccess)->handle(
        $super,
        $operation === 'deactivate' ? 'inactive' : 'active',
        $operation === 'remove-role' ? [] : [$role->id],
        deleting: $operation === 'delete',
        selfService: true,
    )))->toThrow(ValidationException::class);

    expect($super->fresh()->status)->toBe('active');
    $this->assertModelExists($super);
})->with(['delete', 'deactivate', 'remove-role']);

test('one super admin may be removed when another active super admin remains', function () {
    $this->seed(DatabaseSeeder::class);
    $super = User::where('email', 'admin@example.com')->firstOrFail();
    $other = User::factory()->create();
    $other->syncRoles([Role::where('slug', 'super-admin')->firstOrFail()->id]);
    $this->actingAs($other);

    Volt::test('users.index')->call('confirmDelete', $super->id)->call('delete')->assertHasNoErrors();

    $this->assertModelMissing($super);
    expect($other->fresh()->hasRole('super-admin'))->toBeTrue();
});

test('user search and rendering do not execute markup or interpolate SQL', function () {
    $this->seed(DatabaseSeeder::class);
    $target = User::factory()->create(['name' => '<script>alert(42)</script>']);
    $this->actingAs(User::where('email', 'admin@example.com')->firstOrFail());

    Volt::test('users.index')->set('search', $target->email)
        ->assertSee('&lt;script&gt;alert(42)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(42)</script>', false)
        ->set('search', "' OR 1=1 --")->assertSee('No users match');
});

test('audited update stores previous and new values without password hashes', function () {
    $this->seed(DatabaseSeeder::class);
    $this->actingAs(User::where('email', 'admin@example.com')->firstOrFail());
    $target = User::where('email', 'staff@example.com')->firstOrFail();

    Volt::test('users.form', ['user' => $target])->set('name', 'Changed name')->call('save')->assertHasNoErrors();

    $log = AuditLog::where('action', 'user.updated')->where('auditable_id', $target->id)->firstOrFail();
    expect($log->old_values)->toBe(['name' => 'Sample Staff'])
        ->and($log->new_values)->toBe(['name' => 'Changed name']);
});

test('failed user creation does not leave audit entries', function () {
    $this->seed(DatabaseSeeder::class);
    $this->actingAs(User::where('email', 'admin@example.com')->firstOrFail());

    Volt::test('users.form')->set('email', 'admin@example.com')->call('save')->assertHasErrors();

    $this->assertDatabaseEmpty('audit_logs');
});
