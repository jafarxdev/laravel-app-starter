<?php

use App\Actions\ProtectUserAccess;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $roleFilter = '';
    public string $statusFilter = '';
    public bool $confirmingDelete = false;
    #[Locked]
    public ?int $deletingId = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'roleFilter', 'statusFilter'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'roleFilter', 'statusFilter');
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        Gate::authorize('delete', User::findOrFail($id));
        $this->resetValidation();
        $this->deletingId = $id;
        $this->confirmingDelete = true;
    }

    public function delete(ProtectUserAccess $protection): void
    {
        $user = User::findOrFail($this->deletingId);
        Gate::authorize('delete', $user);

        DB::transaction(function () use ($user, $protection): void {
            $protection->handle($user, $user->status, [], deleting: true);
            $user->delete();
        });

        $this->reset('confirmingDelete', 'deletingId');
        session()->flash('success', 'User deleted.');
    }

    public function toggleStatus(int $id, ProtectUserAccess $protection): void
    {
        $user = User::findOrFail($id);
        Gate::authorize('update', $user);
        $status = $user->status === 'active' ? 'inactive' : 'active';

        DB::transaction(function () use ($user, $status, $protection): void {
            $protection->handle($user, $status, $user->roles()->pluck('roles.id')->all());
            $user->update(['status' => $status]);
        });

        session()->flash('success', 'User status updated.');
    }

    public function with(): array
    {
        Gate::authorize('viewAny', User::class);

        return [
            'users' => User::with('roles')
                ->when($this->search !== '', fn ($query) => $query->where(fn ($query) => $query
                    ->where('name', 'like', '%'.$this->search.'%')->orWhere('email', 'like', '%'.$this->search.'%')))
                ->when($this->roleFilter !== '', fn ($query) => $query->whereHas('roles', fn ($roles) => $roles->where('roles.id', $this->roleFilter)))
                ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
                ->latest('id')->paginate(10),
            'roles' => Role::orderBy('name')->get(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div><flux:heading size="xl">Users</flux:heading><flux:text>Manage accounts and access to your application.</flux:text></div>
        @can('create', App\Models\User::class)<flux:button variant="primary" :href="route('users.create')" wire:navigate>Create user</flux:button>@endcan
    </div>
    @if(session('success'))<flux:callout variant="success">{{ session('success') }}</flux:callout>@endif
    <flux:error name="status" />
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <flux:input wire:model.live.debounce.300ms="search" label="Search users" placeholder="Name or email" />
        <flux:select wire:model.live="roleFilter" label="Role"><option value="">All roles</option>@foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->name }}</option>@endforeach</flux:select>
        <flux:select wire:model.live="statusFilter" label="Status"><option value="">All statuses</option><option value="active">Active</option><option value="inactive">Inactive</option></flux:select>
        <div class="flex items-end"><flux:button wire:click="resetFilters">Reset filters</flux:button></div>
    </div>
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-left text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900"><tr>@foreach(['User', 'Roles', 'Status', 'Actions'] as $heading)<th scope="col" class="px-4 py-3 font-medium">{{ $heading }}</th>@endforeach</tr></thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($users as $user)
                    <tr wire:key="user-{{ $user->id }}">
                        <td class="px-4 py-4"><a class="font-medium underline-offset-4 hover:underline" href="{{ route('users.show', $user) }}" wire:navigate>{{ $user->name }}</a><p class="text-zinc-500">{{ $user->email }}</p></td>
                        <td class="px-4 py-4">{{ $user->roles->pluck('name')->join(', ') ?: 'No roles' }}</td>
                        <td class="px-4 py-4"><flux:badge :color="$user->status === 'active' ? 'green' : 'zinc'">{{ ucfirst($user->status) }}</flux:badge></td>
                        <td class="px-4 py-4"><div class="flex gap-2">
                            @can('update', $user)
                                <flux:button size="sm" :href="route('users.edit', $user)" wire:navigate>Edit</flux:button>
                                @if(auth()->id() !== $user->id)<flux:button size="sm" wire:click="toggleStatus({{ $user->id }})" wire:loading.attr="disabled">{{ $user->status === 'active' ? 'Deactivate' : 'Activate' }}</flux:button>@endif
                            @endcan
                            @can('delete', $user)<flux:button size="sm" variant="danger" wire:click="confirmDelete({{ $user->id }})">Delete</flux:button>@endcan
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-12 text-center text-zinc-500">No users match your filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $users->links() }}
    <flux:modal wire:model="confirmingDelete" class="max-w-md">
        <div class="space-y-5"><flux:heading size="lg">Delete user?</flux:heading><flux:text>This permanently removes the account and its role assignments.</flux:text><flux:error name="status" />
        <div class="flex justify-end gap-2"><flux:modal.close><flux:button>Cancel</flux:button></flux:modal.close><flux:button variant="danger" wire:click="delete" wire:loading.attr="disabled">Delete user</flux:button></div></div>
    </flux:modal>
</div>
