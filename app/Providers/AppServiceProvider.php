<?php

namespace App\Providers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped('starter.settings', fn (): array => Setting::values());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['components.app-logo', 'partials.head', 'components.layouts.app.sidebar', 'components.layouts.app'], function ($view): void {
            $view->with('appSettings', app('starter.settings'));
        });

        Gate::before(function (User $user, string $ability): ?bool {
            if ($user->status !== 'active') {
                return false;
            }

            return str_contains($ability, '.') ? $user->hasPermission($ability) : null;
        });
    }
}
