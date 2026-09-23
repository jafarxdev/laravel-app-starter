<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>@include('partials.head')</head>
    <body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <main class="flex min-h-svh items-center justify-center p-5 sm:p-10">
            <div class="w-full max-w-md space-y-8">
                <a href="{{ route('home') }}" class="flex items-center justify-center gap-3" wire:navigate><x-app-logo /></a>
                <div class="space-y-5 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-8 dark:border-zinc-800 dark:bg-zinc-900"><x-ui.flash-messages />{{ $slot }}</div>
                <p class="text-center text-xs text-zinc-500">Secure access to your workspace</p>
            </div>
        </main>
        @fluxScripts
    </body>
</html>
