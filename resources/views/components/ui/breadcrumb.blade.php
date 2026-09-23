@props(['items' => []])
<nav aria-label="{{ __('Breadcrumb') }}" {{ $attributes }}><ol class="flex flex-wrap items-center gap-2 text-sm text-zinc-500">
    @foreach($items as $label => $url)<li class="flex items-center gap-2">@unless($loop->first)<span aria-hidden="true">/</span>@endunless
        @if($url)<a href="{{ $url }}" wire:navigate class="hover:text-accent focus-visible:outline-2 focus-visible:outline-accent">{{ $label }}</a>@else<span aria-current="page">{{ $label }}</span>@endif
    </li>@endforeach
</ol></nav>