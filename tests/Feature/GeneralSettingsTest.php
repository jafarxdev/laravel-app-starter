<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Livewire\Volt\Volt;

test('authorized user can update settings and branding', function () {
    $this->seed(DatabaseSeeder::class);
    $this->actingAs(User::where('email', 'admin@example.com')->firstOrFail());

    Volt::test('general-settings')->set('settings.application_name', 'My Workspace')
        ->set('settings.timezone', 'Asia/Kabul')->call('save')->assertHasNoErrors();

    $this->assertDatabaseHas('settings', ['key' => 'application_name', 'value' => 'My Workspace']);
    $this->get('/dashboard')->assertSee('My Workspace');
    expect(Setting::formatDate(now()->setDate(2026, 1, 2)->startOfDay()))->toBe('2026-01-02');
});

test('read only settings access cannot update through direct action', function () {
    $role = Role::factory()->create();
    $role->permissions()->attach(Permission::factory()->create(['slug' => 'settings.view', 'module' => 'settings']));
    $user = User::factory()->create();
    $user->syncRoles([$role->id]);
    $this->actingAs($user);

    Volt::test('general-settings')->set('settings.application_name', 'Unauthorized')->call('save')->assertForbidden();

    $this->assertDatabaseMissing('settings', ['value' => 'Unauthorized']);
});

test('invalid settings and unexpected keys are rejected', function () {
    $this->seed(DatabaseSeeder::class);
    $this->actingAs(User::where('email', 'admin@example.com')->firstOrFail());

    Volt::test('general-settings')->set('settings.timezone', 'invalid')
        ->set('settings.logo_path', '../../secret.png')->set('settings.date_format', '<script>')
        ->call('save')->assertHasErrors(['settings.timezone', 'settings.logo_path', 'settings.date_format']);
    Volt::test('general-settings')->set('settings.app_key', 'bad')->call('save')->assertHasErrors('settings');

    $this->assertDatabaseMissing('settings', ['key' => 'app_key']);
});

test('settings route rejects users without permission', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('general-settings'))->assertForbidden();
});
