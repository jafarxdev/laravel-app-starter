<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Livewire\Volt\Volt;

test('visitors can switch to Persian and back to English', function () {
    $this->from('/login')->post(route('locale.update'), ['locale' => 'fa'])
        ->assertRedirect('/login')->assertSessionHas('locale', 'fa')->assertCookie('locale', 'fa');

    $this->get('/login')->assertSee('lang="fa" dir="rtl"', false)->assertSee('ورود به حساب شما');
    $this->get('/')->assertSee('شروع کنیم');

    $this->post(route('locale.update'), ['locale' => 'en'])->assertSessionHas('locale', 'en');
    $this->get('/login')->assertSee('lang="en" dir="ltr"', false)->assertSee('Log in to your account');
});

test('locale cookie restores language after the session ends', function () {
    $this->withCookie('locale', 'fa')->get('/login')
        ->assertSee('lang="fa" dir="rtl"', false)->assertSee('ورود به حساب شما');
});

test('invalid language selections do not replace the saved locale', function (mixed $locale) {
    $this->withSession(['locale' => 'fa'])->post(route('locale.update'), ['locale' => $locale])
        ->assertSessionHasErrors('locale')->assertSessionHas('locale', 'fa');
})->with(['unsupported' => ['de'], 'path' => ['../../en'], 'array' => [['fa']], 'missing' => [null]]);

test('invalid stored locales safely fall back to English', function () {
    $this->withSession(['locale' => ['fa']])->get('/login')->assertSee('lang="en" dir="ltr"', false);
});

test('language switch does not redirect to an external referrer', function () {
    $this->from('https://example.org/phishing')->post(route('locale.update'), ['locale' => 'fa'])
        ->assertRedirect(route('home'));
});

test('Persian validation and authentication errors survive Livewire updates', function () {
    $this->withSession(['locale' => 'fa'])->get('/login');

    Volt::test('auth.login')->call('login')->assertHasErrors('email')
        ->assertSee('وارد کردن ایمیل الزامی است.')
        ->set('email', 'unknown@example.com')->set('password', 'wrong-password')->call('login')
        ->assertHasErrors('email')->assertSee('اطلاعات واردشده با حساب‌های ما مطابقت ندارد.');
});

test('Persian administration and account pages render with translated navigation', function (string $path) {
    $this->seed(DatabaseSeeder::class);
    $this->actingAs(User::where('email', 'admin@example.com')->firstOrFail());

    $this->withSession(['locale' => 'fa'])->get($path)
        ->assertOk()->assertSee('lang="fa" dir="rtl"', false)->assertSee('محیط کاری')->assertSee('زبان')->assertDontSee('تغییر زبان');
})->with(['/dashboard', '/roles', '/permissions', '/users', '/users/create', '/general-settings', '/audit-logs', '/settings/profile', '/settings/password', '/settings/appearance']);

test('authorized editors can save search and clear Persian content translations', function (string $component, string $model, string $table) {
    $this->seed(DatabaseSeeder::class);
    $this->actingAs(User::where('email', 'admin@example.com')->firstOrFail());
    $record = $model::factory()->create(['name' => 'Review', 'description' => 'Original description']);
    $this->withSession(['locale' => 'fa'])->get('/dashboard');

    Volt::test($component)->call('edit', $record->id)
        ->set('name_fa', 'بازبینی')->set('description_fa', 'توضیحات بازبینی')
        ->call('save')->assertHasNoErrors()->set('search', 'بازبینی')
        ->assertSee('توضیحات بازبینی');

    $this->assertDatabaseHas($table, ['id' => $record->id, 'name_fa' => 'بازبینی', 'description_fa' => 'توضیحات بازبینی']);
    expect($record->fresh()->localizedName())->toBe('بازبینی');
    app()->setLocale('en');
    expect($record->fresh()->localizedName())->toBe('Review');

    Volt::test($component)->call('edit', $record->id)->set('name_fa', '')->set('description_fa', '')
        ->call('save')->assertHasNoErrors();

    app()->setLocale('fa');
    expect($record->fresh()->localizedName())->toBe('Review');
    expect($record->fresh()->localizedDescription())->toBe('Original description');
})->with([
    'roles' => ['roles.index', Role::class, 'roles'],
    'permissions' => ['permissions.index', Permission::class, 'permissions'],
]);

test('content translations validate length before saving', function (string $component, string $model) {
    $this->seed(DatabaseSeeder::class);
    $this->actingAs(User::where('email', 'admin@example.com')->firstOrFail());
    $record = $model::factory()->create();
    $this->withSession(['locale' => 'fa'])->get('/dashboard');

    Volt::test($component)->call('edit', $record->id)
        ->set('name_fa', str_repeat('ن', 256))->set('description_fa', str_repeat('ت', 1001))
        ->call('save')->assertHasErrors(['name_fa', 'description_fa'])
        ->assertSee('نام فارسی / دری نباید بیش از 255 نویسه داشته باشد.');

    expect($record->fresh()->name_fa)->toBeNull();
})->with([['roles.index', Role::class], ['permissions.index', Permission::class]]);

test('read only users cannot save content translations through direct actions', function (string $component, string $model, string $permissionSlug) {
    $role = Role::factory()->create();
    $role->permissions()->attach(Permission::factory()->create(['slug' => $permissionSlug]));
    $user = User::factory()->create();
    $user->syncRoles([$role->id]);
    $record = $model::factory()->create();
    $this->actingAs($user);

    Volt::test($component)->set('name_fa', 'بدون اجازه')->call('save')->assertForbidden();

    expect($record->fresh()->name_fa)->toBeNull();
})->with([['roles.index', Role::class, 'roles.view'], ['permissions.index', Permission::class, 'permissions.view']]);

test('Persian branding is editable while original branding remains available', function () {
    $this->seed(DatabaseSeeder::class);
    $this->actingAs(User::where('email', 'admin@example.com')->firstOrFail());

    Volt::test('general-settings')->set('settings.application_name', 'My Workspace')
        ->set('settings.application_name_fa', 'محیط کاری من')->set('settings.organization_name_fa', 'سازمان من')
        ->call('save')->assertHasNoErrors();

    $this->assertDatabaseHas('settings', ['key' => 'organization_name_fa', 'value' => 'سازمان من']);
    $this->withSession(['locale' => 'fa'])->get('/dashboard')->assertSee('محیط کاری من');
    $this->withSession(['locale' => 'en'])->get('/dashboard')->assertSee('My Workspace');
});

test('translated database content is escaped when rendered', function () {
    $this->seed(DatabaseSeeder::class);
    $this->actingAs(User::where('email', 'admin@example.com')->firstOrFail());
    Role::factory()->create(['name_fa' => '<script>alert(1)</script>']);

    $this->withSession(['locale' => 'fa'])->get('/roles')
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
});
