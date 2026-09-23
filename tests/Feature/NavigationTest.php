<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;

test('viewer navigation hides management links', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::where('email', 'viewer@example.com')->firstOrFail();

    $this->actingAs($user)->get('/dashboard')->assertOk()
        ->assertDontSee('href="'.route('users.index').'"', false)
        ->assertDontSee('href="'.route('roles.index').'"', false)
        ->assertSee('My profile');
});

test('super admin navigation exposes management links', function () {
    $this->seed(DatabaseSeeder::class);
    $user = User::where('email', 'admin@example.com')->firstOrFail();

    $this->actingAs($user)->get('/dashboard')
        ->assertSee('href="'.route('users.index').'"', false)
        ->assertSee('href="'.route('roles.index').'"', false)
        ->assertSee('href="'.route('permissions.index').'"', false);
});
