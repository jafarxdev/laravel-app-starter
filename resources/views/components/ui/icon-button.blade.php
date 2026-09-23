@props(['label', 'icon'])
<flux:button :icon="$icon" :aria-label="$label" :title="$label" {{ $attributes }} />