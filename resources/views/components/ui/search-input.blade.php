@props(['label' => 'Search', 'placeholder' => 'Search records...'])
<x-ui.input type="search" :label="__($label)" :placeholder="__($placeholder)" {{ $attributes }} />