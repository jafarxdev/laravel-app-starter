@props(['label', 'value', 'description' => null])
<x-ui.card {{ $attributes }}>
    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $label }}</p>
    <p class="mt-3 text-3xl font-semibold tracking-tight tabular-nums">{{ $value }}</p>
    @if($description)<p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ $description }}</p>@endif
</x-ui.card>