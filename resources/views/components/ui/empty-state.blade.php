@props(['title' => 'Nothing here yet', 'description' => 'Create your first record to get started.'])
<div {{ $attributes->class('flex flex-col items-center gap-3 px-6 py-12 text-center') }}>
    <h3 class="font-semibold">{{ $title }}</h3>
    <p class="max-w-sm text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
    {{ $slot }}
</div>