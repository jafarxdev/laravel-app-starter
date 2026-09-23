<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;

new class extends Component {
    public User $user;

    public function mount(User $user): void
    {
        Gate::authorize('view', $user);
        $this->user = $user;
    }

    public function with(): array
    {
        Gate::authorize('view', $this->user);
        $this->user->load('roles');

        return [];
    }
}; ?>

<div class="mx-auto max-w-3xl space-y-6">
    <div class="flex items-center justify-between gap-4"><flux:heading size="xl">{{ $user->name }}</flux:heading>
        @can('update', $user)<flux:button :href="route('users.edit', $user)" wire:navigate>Edit user</flux:button>@endcan
    </div>
    <dl class="grid gap-6 rounded-xl border border-zinc-200 p-6 sm:grid-cols-2 dark:border-zinc-700">
        @foreach(['Email' => $user->email, 'Status' => ucfirst($user->status), 'Roles' => $user->roles->pluck('name')->join(', ') ?: 'No roles', 'Created' => $user->created_at->format('Y-m-d')] as $label => $value)
            <div><dt class="text-sm text-zinc-500">{{ $label }}</dt><dd class="mt-1 font-medium">{{ $value }}</dd></div>
        @endforeach
    </dl>
    <flux:button :href="route('users.index')" wire:navigate>Back to users</flux:button>
</div>
