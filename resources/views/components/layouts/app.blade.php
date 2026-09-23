<x-layouts.app.sidebar>
    <flux:main id="main-content" class="min-w-0">
        <div class="mx-auto flex min-h-[80vh] max-w-7xl flex-col gap-6">
            <nav aria-label="{{ __('Breadcrumb') }}" class="text-sm text-zinc-500">
                @can('dashboard.view')<a href="{{ route('dashboard') }}" class="hover:text-accent focus-visible:outline-2 focus-visible:outline-accent" wire:navigate>{{ __('Workspace') }}</a><span aria-hidden="true" class="px-2">/</span>@endcan
                <span aria-current="page">{{ __($title ?? (string) str(request()->route()?->getName() ?? 'Workspace')->replace(['.', '-'], ' ')->headline()) }}</span>
            </nav>
            <div class="flex-1">{{ $slot }}</div>
            <footer class="flex flex-wrap justify-between gap-2 border-t border-zinc-200 pt-5 text-xs text-zinc-500 dark:border-zinc-800"><span>{{ $appSettings['application_name'] }}</span><span>&copy; {{ now()->year }} · All rights reserved</span></footer>
        </div>
    </flux:main>
</x-layouts.app.sidebar>
