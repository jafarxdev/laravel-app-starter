@props(['label' => 'Search', 'placeholder' => 'Search records...'])
<x-ui.input type="search" :label="$label" :placeholder="$placeholder" {{ $attributes }} />