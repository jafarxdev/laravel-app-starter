@props(['status'])
<flux:badge :color="$status === 'active' ? 'green' : 'zinc'" {{ $attributes }}>{{ __(ucfirst($status)) }}</flux:badge>