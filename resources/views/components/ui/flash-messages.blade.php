<div class="space-y-3" aria-live="polite">
    @foreach(['success', 'error', 'warning', 'info'] as $type)
        @if(session()->has($type))<x-ui.alert :type="$type">{{ session($type) }}</x-ui.alert>@endif
    @endforeach
</div>