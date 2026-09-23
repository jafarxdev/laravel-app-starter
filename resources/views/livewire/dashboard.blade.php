<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Volt\Component;

new class extends Component {
    public function with(): array
    {
        Gate::authorize('dashboard.view');
        $user = auth()->user();
        $user->loadMissing('roles.permissions');

        return [
            'currentUser' => $user,
            'permissionCount' => $user->hasRole('super-admin')
                ? Permission::count() : $user->roles->flatMap->permissions->unique('id')->count(),
            'userCount' => $user->can('users.view') ? User::count() : null,
            'roleCount' => $user->can('roles.view') ? Role::count() : null,
            'recentUsers' => $user->can('users.view') ? User::latest('id')->limit(5)->get() : collect(),
        ];
    }
}; ?>

<div class="space-y-6">
    <x-ui.page-header title="Dashboard" description="An overview of your workspace and access." />
    <section class="relative overflow-hidden rounded-2xl bg-indigo-600 p-6 text-white sm:p-8">
        <div class="max-w-2xl space-y-3"><p class="text-xs font-medium uppercase tracking-widest text-indigo-200">Welcome back</p><h2 class="text-2xl font-semibold tracking-tight">{{ $currentUser->name }}</h2><p class="text-sm text-indigo-100">Everything you need to manage your workspace, in one place.</p>
            <div class="flex flex-wrap gap-2 pt-2">@forelse($currentUser->roles as $role)<span class="rounded-full bg-white/15 px-3 py-1 text-xs">{{ $role->name }}</span>@empty<span class="text-sm text-indigo-100">No roles assigned. Contact your administrator.</span>@endforelse</div>
        </div>
    </section>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-ui.stats-card label="Available permissions" :value="$permissionCount" :description="$currentUser->hasRole('super-admin') ? 'Full access, including future permissions' : 'Combined from your assigned roles'" />
        @if($userCount !== null)<x-ui.stats-card label="Total users" :value="$userCount" description="Accounts in this workspace" />@endif
        @if($roleCount !== null)<x-ui.stats-card label="Total roles" :value="$roleCount" description="Reusable permission groups" />@endif
    </div>
    <div class="grid gap-6 lg:grid-cols-3">
        <x-ui.card title="Quick actions">
            <div class="flex flex-col gap-3">
                @can('users.create')<x-ui.button :href="route('users.create')" variant="primary" wire:navigate>Create user</x-ui.button>@endcan
                @can('users.view')<x-ui.button :href="route('users.index')" wire:navigate>Manage users</x-ui.button>@endcan
                @can('roles.view')<x-ui.button :href="route('roles.index')" wire:navigate>Manage roles</x-ui.button>@endcan
                <x-ui.button :href="route('settings.profile')" wire:navigate>My profile</x-ui.button>
            </div>
        </x-ui.card>
        @can('users.view')
            <x-ui.card title="Recent users" class="lg:col-span-2">
                <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">@forelse($recentUsers as $user)<li class="flex items-center justify-between gap-3 py-3"><div class="min-w-0"><a href="{{ route('users.show', $user) }}" class="font-medium hover:underline" wire:navigate>{{ $user->name }}</a><p class="truncate text-sm text-zinc-500">{{ $user->email }}</p></div><x-ui.status-badge :status="$user->status" /></li>@empty<li><x-ui.empty-state title="No users yet" description="New accounts will appear here." /></li>@endforelse</ul>
            </x-ui.card>
        @else
            <x-ui.card title="Your account" class="lg:col-span-2"><dl class="space-y-4"><div><dt class="text-sm text-zinc-500">Email</dt><dd>{{ $currentUser->email }}</dd></div><div><dt class="text-sm text-zinc-500">Account status</dt><dd class="mt-1"><x-ui.status-badge :status="$currentUser->status" /></dd></div></dl></x-ui.card>
        @endcan
    </div>
</div>
