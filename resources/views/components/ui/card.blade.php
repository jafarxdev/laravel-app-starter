@props(['title' => null])
<section {{ $attributes->class('rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900') }}>
    @if($title)<h2 class="mb-4 text-base font-semibold">{{ $title }}</h2>@endif
    {{ $slot }}
</section>