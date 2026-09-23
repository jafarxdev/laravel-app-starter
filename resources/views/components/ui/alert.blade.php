@props(['type' => 'info'])
@php($variant = match($type) { 'error' => 'danger', 'warning' => 'warning', 'success' => 'success', default => 'secondary' })
<flux:callout :variant="$variant" role="{{ $type === 'error' ? 'alert' : 'status' }}" {{ $attributes }}>{{ $slot }}</flux:callout>