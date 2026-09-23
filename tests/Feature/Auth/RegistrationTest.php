<?php

use Livewire\Volt\Volt;

test('public registration screen is disabled', function () {
    $response = $this->get('/register');

    $response->assertNotFound();
});

test('direct registration action is disabled', function () {
    $response = Volt::test('auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register');

    $response->assertNotFound();

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
});
