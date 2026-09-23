@props(['status'])
<flux:badge :color="$status === 'active' ? 'green' : 'zinc'" {{ $attributes }}>{{ ucfirst($status) }}</flux:badge>