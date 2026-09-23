<form method="POST" action="{{ route('locale.update') }}" {{ $attributes->class('flex flex-wrap items-center gap-2 text-sm') }}>
    @csrf
    <label>
        <span class="sr-only">{{ __('Language') }}</span>
        <select name="locale" onchange="this.form.requestSubmit()" class="rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
            @foreach(config('starter.locales') as $locale => $label)
                <option value="{{ $locale }}" @selected(app()->getLocale() === $locale)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
</form>
