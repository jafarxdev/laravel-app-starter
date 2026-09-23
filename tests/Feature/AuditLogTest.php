<?php

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Livewire\Volt\Volt;

test('user changes and role assignments are audited without credentials', function () {
    $this->seed(DatabaseSeeder::class);
    $admin = User::where('email', 'admin@example.com')->firstOrFail();
    $this->actingAs($admin);
    $role = Role::where('slug', 'staff')->firstOrFail();

    Volt::test('users.form')->set('name', 'Audited User')->set('email', 'audited@example.com')
        ->set('password', 'secret-password')->set('password_confirmation', 'secret-password')
        ->set('selectedRoles', [$role->id])->call('save')->assertHasNoErrors();

    $user = User::where('email', 'audited@example.com')->firstOrFail();
    $this->assertDatabaseHas('audit_logs', ['action' => 'user.created', 'auditable_id' => $user->id, 'user_id' => $admin->id]);
    $this->assertDatabaseHas('audit_logs', ['action' => 'user.roles-assigned', 'auditable_id' => $user->id]);
    $serialized = AuditLog::all()->toJson();
    expect($serialized)->not->toContain('secret-password')->not->toContain($user->password)->not->toContain('remember_token');
});

test('settings updates and permission assignments are audited', function () {
    $this->seed(DatabaseSeeder::class);
    $this->actingAs(User::where('email', 'admin@example.com')->firstOrFail());

    Volt::test('general-settings')->set('settings.application_name', 'Audited workspace')->call('save')->assertHasNoErrors();
    $role = Role::where('slug', 'manager')->firstOrFail();
    Volt::test('roles.index')->call('edit', $role->id)->set('selectedPermissions', [])->call('save')->assertHasNoErrors();

    $this->assertDatabaseHas('audit_logs', ['action' => 'settings.updated']);
    $this->assertDatabaseHas('audit_logs', ['action' => 'role.permissions-assigned', 'auditable_id' => $role->id]);
    expect(AuditLog::where('action', 'settings.updated')->first()->new_values['application_name'])->toBe('Audited workspace');
});

test('audit viewer requires permission and safely renders changes', function () {
    $this->seed(DatabaseSeeder::class);
    AuditLog::factory()->create(['new_values' => ['name' => '<script>alert(1)</script>']]);
    $this->actingAs(User::where('email', 'admin@example.com')->firstOrFail());

    $this->get(route('audit-logs'))->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    $this->actingAs(User::where('email', 'viewer@example.com')->firstOrFail());
    $this->get(route('audit-logs'))->assertForbidden();
    Volt::test('audit-logs')->assertForbidden();
});

test('audit entries survive deletion of their actor', function () {
    $user = User::factory()->create();
    $log = AuditLog::factory()->create(['user_id' => $user->id]);

    $user->delete();

    expect($log->fresh()->user_id)->toBeNull();
});
