<?php

use App\Models\User;
use Livewire\Volt\Volt as LivewireVolt;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = LivewireVolt::test('auth.login')
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    LivewireVolt::test('auth.login')
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors(['email']);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('inactive users cannot log in', function () {
    $user = User::factory()->inactive()->create();

    LivewireVolt::test('auth.login')->set('email', $user->email)
        ->set('password', 'password')->call('login')->assertHasErrors(['email']);

    $this->assertGuest();
});

test('deactivated users lose their existing session', function () {
    $user = User::factory()->inactive()->create();

    $this->actingAs($user)->get('/dashboard')->assertRedirect(route('login'));

    $this->assertGuest();
});

test('login attempts are throttled', function () {
    $user = User::factory()->create();

    for ($attempt = 0; $attempt < 5; $attempt++) {
        LivewireVolt::test('auth.login')->set('email', $user->email)
            ->set('password', 'incorrect')->call('login')->assertHasErrors('email');
    }

    LivewireVolt::test('auth.login')->set('email', $user->email)
        ->set('password', 'password')->call('login')->assertHasErrors('email');

    $this->assertGuest();
});
