<?php

use Illuminate\Support\Facades\Route;

test('error pages hide sensitive exception details', function (int $status, string $title) {
    config(['app.debug' => false]);
    Route::get('/test-error', fn () => abort($status, 'PRIVATE_DATABASE_PASSWORD'));

    $this->get('/test-error')->assertStatus($status)->assertSee($title)
        ->assertDontSee('PRIVATE_DATABASE_PASSWORD')->assertDontSee('Stack trace');
})->with([[403, 'Access denied'], [404, 'Page not found'], [419, 'Session expired'], [500, 'Something went wrong']]);

test('redirect flash messages render safely on the login page', function (string $type) {
    $this->withSession([$type => '<script>unsafe</script>'])->get('/login')
        ->assertSee('&lt;script&gt;unsafe&lt;/script&gt;', false)->assertDontSee('<script>unsafe</script>', false);
})->with(['success', 'error', 'warning', 'info']);
