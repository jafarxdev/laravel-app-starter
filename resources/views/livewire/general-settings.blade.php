<?php

use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component {
    public array $settings = [];

    public function mount(): void
    {
        Gate::authorize('settings.view');
        $this->settings = Setting::values();
    }

    public function save(): void
    {
        Gate::authorize('settings.update');
        $validated = $this->validate([
            'settings' => ['array:application_name,short_name,organization_name,application_name_fa,short_name_fa,organization_name_fa,contact_email,contact_phone,logo_path,timezone,date_format'],
            'settings.application_name_fa' => ['nullable', 'string', 'max:100'],
            'settings.short_name_fa' => ['nullable', 'string', 'max:12'],
            'settings.organization_name_fa' => ['nullable', 'string', 'max:150'],
            'settings.application_name' => ['required', 'string', 'max:100'],
            'settings.short_name' => ['required', 'string', 'max:12'],
            'settings.organization_name' => ['required', 'string', 'max:150'],
            'settings.contact_email' => ['nullable', 'email', 'max:255'],
            'settings.contact_phone' => ['nullable', 'string', 'max:40'],
            'settings.logo_path' => ['nullable', 'string', 'max:255', 'regex:~^(storage|images)/[a-zA-Z0-9_/-]+\.(png|jpg|jpeg|webp)$~'],
            'settings.timezone' => ['required', 'timezone'],
            'settings.date_format' => ['required', Rule::in(['Y-m-d', 'd/m/Y', 'm/d/Y', 'd M Y'])],
        ])['settings'];

        DB::transaction(function () use ($validated): void {
            $old = Setting::values();
            foreach ($validated as $key => $value) {
                Setting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
            \App\Models\AuditLog::record('settings.updated', old: $old, new: $validated);
        });
        app()->forgetInstance('starter.settings');
        session()->flash('success', __('Settings updated.'));
    }

    public function with(): array
    {
        Gate::authorize('settings.view');

        return ['timezones' => DateTimeZone::listIdentifiers()];
    }
}; ?>

<div class="mx-auto max-w-4xl space-y-6">
    <x-ui.page-header title="{{ __('General settings') }}" description="{{ __('Customize the identity and presentation of your application.') }}" />
    <x-ui.flash-messages />
    <form wire:submit="save" class="space-y-6">
        <x-ui.form-section title="{{ __('Application identity') }}" description="{{ __('These values appear in the application layout.') }}">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-ui.input wire:model="settings.application_name" label="{{ __('Application name') }}" required />
                <x-ui.input wire:model="settings.short_name" label="{{ __('Short name') }}" required />
                <x-ui.input wire:model="settings.organization_name" label="{{ __('Organization name') }}" required />
                <x-ui.input wire:model="settings.application_name_fa" label="{{ __('Application name (Persian / Dari)') }}" dir="rtl" description="{{ __('Optional. Falls back to the original text when empty.') }}" />
                <x-ui.input wire:model="settings.short_name_fa" label="{{ __('Short name (Persian / Dari)') }}" dir="rtl" />
                <x-ui.input wire:model="settings.organization_name_fa" label="{{ __('Organization name (Persian / Dari)') }}" dir="rtl" />
                <x-ui.input wire:model="settings.logo_path" label="{{ __('Logo path (optional)') }}" placeholder="images/logo.png" description="{{ __('Relative path to an existing image in the public folder.') }}" />
            </div>
        </x-ui.form-section>
        <x-ui.form-section title="{{ __('Contact and regional preferences') }}">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-ui.input wire:model="settings.contact_email" label="{{ __('Contact email') }}" type="email" />
                <x-ui.input wire:model="settings.contact_phone" label="{{ __('Contact phone') }}" type="tel" />
                <x-ui.select wire:model="settings.timezone" label="{{ __('Display timezone') }}">@foreach($timezones as $timezone)<option value="{{ $timezone }}">{{ $timezone }}</option>@endforeach</x-ui.select>
                <x-ui.select wire:model="settings.date_format" label="{{ __('Date format') }}">@foreach(['Y-m-d', 'd/m/Y', 'm/d/Y', 'd M Y'] as $format)<option value="{{ $format }}">{{ $format }}</option>@endforeach</x-ui.select>
            </div>
        </x-ui.form-section>
        <x-ui.input-error name="settings" />
        @can('settings.update')<div class="flex justify-end"><x-ui.button type="submit" variant="primary" wire:loading.attr="disabled">{{ __('Save settings') }}</x-ui.button></div>@else<x-ui.alert>{{ __('You have read-only access to these settings.') }}</x-ui.alert>@endcan
    </form>
</div>
