@if($appSettings['logo_path'])
    <img src="{{ asset($appSettings['logo_path']) }}" alt="{{ __(':name logo', ['name' => $appSettings['application_name']]) }}" class="size-9 rounded-xl object-contain" />
@else
    <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent px-1 text-xs font-bold text-accent-foreground">{{ $appSettings['short_name'] }}</div>
@endif
<div class="min-w-0 text-sm font-semibold tracking-tight">{{ $appSettings['application_name'] }}</div>
