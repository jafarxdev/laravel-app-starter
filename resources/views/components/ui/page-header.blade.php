@props(['title', 'description' => null])
<header {{ $attributes->class('flex flex-wrap items-start justify-between gap-4') }}>
    <div><h1 class="text-2xl font-semibold tracking-tight">{{ $title }}</h1>@if($description)<p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>@endif</div>
    @isset($actions)<div class="flex flex-wrap gap-2">{{ $actions }}</div>@endisset
    {{ $slot }}
</header>