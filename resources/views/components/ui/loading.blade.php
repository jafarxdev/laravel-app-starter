@props(['label' => 'Working...'])
<div role="status" {{ $attributes->class('text-sm text-zinc-500') }}><span class="motion-safe:animate-pulse">{{ __($label) }}</span></div>