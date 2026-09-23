@props(['title', 'description' => null])
<fieldset {{ $attributes->class('space-y-5 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900') }}>
    <legend class="px-2 font-semibold">{{ $title }}</legend>
    @if($description)<p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>@endif
    {{ $slot }}
</fieldset>