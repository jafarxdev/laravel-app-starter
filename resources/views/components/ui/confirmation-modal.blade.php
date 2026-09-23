@props(['title' => 'Confirm deletion', 'description' => 'This action cannot be undone.', 'action' => 'delete', 'confirmLabel' => 'Delete'])
<flux:modal {{ $attributes->class('max-w-md') }}>
    <div class="space-y-5">
        <flux:heading size="lg">{{ $title }}</flux:heading>
        <flux:text>{{ $description }}</flux:text>
        {{ $slot }}
        <div class="flex justify-end gap-3">
            <flux:modal.close><x-ui.button>Cancel</x-ui.button></flux:modal.close>
            <x-ui.button variant="danger" wire:click="{{ $action }}" wire:loading.attr="disabled">{{ $confirmLabel }}</x-ui.button>
        </div>
    </div>
</flux:modal>