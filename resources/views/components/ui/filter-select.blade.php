@props(['label', 'placeholder' => 'All'])
<x-ui.select :label="__($label)" {{ $attributes }}><option value="">{{ __($placeholder) }}</option>{{ $slot }}</x-ui.select>