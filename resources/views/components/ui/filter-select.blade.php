@props(['label', 'placeholder' => 'All'])
<x-ui.select :label="$label" {{ $attributes }}><option value="">{{ $placeholder }}</option>{{ $slot }}</x-ui.select>