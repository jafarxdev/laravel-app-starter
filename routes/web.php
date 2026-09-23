<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Volt::route('users', 'users.index')->middleware('can:users.view')->name('users.index');
    Volt::route('users/create', 'users.form')->middleware('can:users.create')->name('users.create');
    Volt::route('users/{user}/edit', 'users.form')->middleware('can:users.update')->name('users.edit');
    Volt::route('users/{user}', 'users.show')->middleware('can:users.view')->name('users.show');

    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';
