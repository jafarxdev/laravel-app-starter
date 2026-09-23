<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

function signInAsStarterAdmin(): User
{
    test()->seed(DatabaseSeeder::class);
    $admin = User::where('email', 'admin@example.com')->firstOrFail();
    test()->actingAs($admin);

    return $admin;
}

test('authorized user can browse filter and view users', function () {
    signInAsStarterAdmin();
    $viewer = User::where('email', 'viewer@example.com')->firstOrFail();

    $this->get(route('users.index'))->assertOk();
    $this->get(route('users.show', $viewer))->assertSee($viewer->email);
    Volt::test('users.index')->set('search', $viewer->email)
        ->assertSee('Sample Viewer')->assertDontSee('Sample Manager')
        ->set('statusFilter', 'inactive')->assertSee('No users match')
        ->call('resetFilters')->assertSee('Sample Manager');
});

test('user can be created with several roles and a hashed password', function () {
    signInAsStarterAdmin();
    $roles = Role::whereIn('slug', ['staff', 'viewer'])->pluck('id')->all();

    Volt::test('users.form')->set('name', 'New Account')->set('email', 'new@example.com')
        ->set('password', 'secure-password')->set('password_confirmation', 'secure-password')
        ->set('selectedRoles', $roles)->call('save')->assertHasNoErrors()->assertRedirect(route('users.index'));

    $user = User::where('email', 'new@example.com')->firstOrFail();
    expect(Hash::check('secure-password', $user->password))->toBeTrue()
        ->and($user->roles()->count())->toBe(2);
});

test('duplicate email and missing password do not create a user', function () {
    signInAsStarterAdmin();

    Volt::test('users.form')->set('name', 'Duplicate')->set('email', 'admin@example.com')
        ->call('save')->assertHasErrors(['email', 'password']);

    $this->assertDatabaseCount('users', 5);
});

test('editing user with blank password preserves the hash and updates roles', function () {
    signInAsStarterAdmin();
    $user = User::where('email', 'staff@example.com')->firstOrFail();
    $password = $user->password;
    $role = Role::where('slug', 'manager')->firstOrFail();

    Volt::test('users.form', ['user' => $user])->set('name', 'Updated account')
        ->set('selectedRoles', [$role->id])->call('save')->assertHasNoErrors();

    expect($user->fresh()->name)->toBe('Updated account')
        ->and($user->fresh()->password)->toBe($password)
        ->and($user->fresh()->hasRole('manager'))->toBeTrue();
});

test('administrators cannot deactivate or remove their own super role', function () {
    $admin = signInAsStarterAdmin();

    Volt::test('users.form', ['user' => $admin])->set('status', 'inactive')->call('save')->assertHasErrors('status');
    Volt::test('users.form', ['user' => $admin])->set('selectedRoles', [])->call('save')->assertHasErrors('status');

    expect($admin->fresh()->status)->toBe('active')
        ->and($admin->fresh()->hasRole('super-admin'))->toBeTrue();
});

test('last active super admin cannot be deleted through profile', function () {
    $admin = signInAsStarterAdmin();

    Volt::test('settings.delete-user-form')->set('password', 'password')->call('deleteUser')->assertHasErrors('status');

    $this->assertModelExists($admin);
    $this->assertAuthenticatedAs($admin);
});

test('authorized user can deactivate and delete an ordinary account', function () {
    signInAsStarterAdmin();
    $user = User::where('email', 'staff@example.com')->firstOrFail();

    Volt::test('users.index')->call('toggleStatus', $user->id)->assertHasNoErrors();
    expect($user->fresh()->status)->toBe('inactive');

    Volt::test('users.index')->call('confirmDelete', $user->id)->call('delete')->assertHasNoErrors();

    $this->assertModelMissing($user);
});

test('ordinary administrators cannot assign super admin role', function () {
    signInAsStarterAdmin();
    $this->actingAs(User::where('email', 'sample-admin@example.com')->firstOrFail());
    $superRole = Role::where('slug', 'super-admin')->firstOrFail();

    Volt::test('users.form')->set('name', 'Escalation')->set('email', 'escalation@example.com')
        ->set('password', 'secure-password')->set('password_confirmation', 'secure-password')
        ->set('selectedRoles', [$superRole->id])->call('save')->assertForbidden();

    $this->assertDatabaseMissing('users', ['email' => 'escalation@example.com']);
});

test('users without permission cannot access routes or direct actions', function () {
    signInAsStarterAdmin();
    $this->actingAs(User::where('email', 'viewer@example.com')->firstOrFail());

    $this->get(route('users.index'))->assertForbidden();
    $this->get(route('users.create'))->assertForbidden();
    Volt::test('users.index')->assertForbidden();
    Volt::test('users.form')->assertForbidden();
});

test('revoking permissions after mounting prevents saving', function () {
    signInAsStarterAdmin();
    $actor = User::where('email', 'sample-admin@example.com')->firstOrFail();
    $this->actingAs($actor);
    $component = Volt::test('users.form')->set('name', 'Revoked')->set('email', 'revoked@example.com')
        ->set('password', 'secure-password')->set('password_confirmation', 'secure-password');
    $actor->syncRoles([]);

    $component->call('save')->assertForbidden();

    $this->assertDatabaseMissing('users', ['email' => 'revoked@example.com']);
});
