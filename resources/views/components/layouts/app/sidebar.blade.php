<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>@include('partials.head')</head>
    <body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-lg focus:bg-white focus:p-3 focus:text-zinc-900">Skip to content</a>
        <flux:sidebar sticky stashable class="border-r border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" aria-label="Close navigation" />
            <a href="{{ route('dashboard') }}" class="mb-6 flex items-center gap-2" wire:navigate><x-app-logo /></a>
            <flux:navlist variant="outline" aria-label="Main navigation">
                <flux:navlist.group heading="Workspace" class="grid gap-1">
                    @foreach(config('navigation') as $item)
                        @if(Route::has($item['route']))
                            @can($item['permission'])
                                <flux:navlist.item :href="route($item['route'])" :current="request()->routeIs($item['active'])" wire:navigate>{{ $item['label'] }}</flux:navlist.item>
                            @endcan
                        @endif
                    @endforeach
                </flux:navlist.group>
            </flux:navlist>
            <flux:spacer />
            <div class="rounded-lg bg-zinc-50 p-3 text-xs text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">Your workspace, your workflow.</div>
            <flux:navlist><flux:navlist.item :href="route('settings.profile')" :current="request()->routeIs('settings.*')" wire:navigate>My profile</flux:navlist.item></flux:navlist>
        </flux:sidebar>

        <flux:header class="border-b border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" aria-label="Open navigation" />
            <span class="hidden text-sm font-medium text-zinc-500 sm:block">{{ config('app.name') }}</span>
            <flux:spacer />
            <flux:dropdown position="bottom" align="end">
                <flux:profile :name="auth()->user()->name" :initials="auth()->user()->initials()" icon-trailing="chevron-down" />
                <flux:menu>
                    <div class="max-w-64 truncate px-3 py-2 text-xs text-zinc-500">{{ auth()->user()->email }}</div>
                    <flux:menu.item :href="route('settings.profile')" wire:navigate>My profile</flux:menu.item>
                    <flux:menu.item :href="route('settings.password')" wire:navigate>Change password</flux:menu.item>
                    <flux:menu.item :href="route('settings.appearance')" wire:navigate>Appearance</flux:menu.item>
                    <flux:menu.separator />
                    <form method="POST" action="{{ route('logout') }}">@csrf<flux:menu.item as="button" type="submit" class="w-full">Log out</flux:menu.item></form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>
        {{ $slot }}
        @fluxScripts
    </body>
</html>
