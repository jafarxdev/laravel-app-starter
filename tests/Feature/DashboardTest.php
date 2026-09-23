<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Livewire\Volt\Volt;

test('guests are redirected to the login page', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('authorized users can visit the dashboard', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::where('email', 'admin@example.com')->firstOrFail();

    $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('Total users')->assertSee('Recent users');
});

test('viewer dashboard does not expose administrative statistics', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::where('email', 'viewer@example.com')->firstOrFail();

    $this->actingAs($user)->get('/dashboard')->assertSee('Available permissions')
        ->assertDontSee('Total users')->assertDontSee('Total roles')->assertDontSee('Recent users');
});

test('dashboard requires its permission on route and component', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/dashboard')->assertForbidden();
    Volt::test('dashboard')->assertForbidden();
});
