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
        session()->flash('success', __('User deleted.'));
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

        session()->flash('success', __('User status updated.'));
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
        <div><flux:heading size="xl">{{ __('Users') }}</flux:heading><flux:text>{{ __('Manage accounts and access to your application.') }}</flux:text></div>
        @can('create', App\Models\User::class)<x-ui.button variant="primary" :href="route('users.create')" wire:navigate>{{ __('Create user') }}</x-ui.button>@endcan
    </div>
    <x-ui.flash-messages />
    <flux:error name="status" />
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.input wire:model.live.debounce.300ms="search" label="{{ __('Search users') }}" placeholder="{{ __('Name or email') }}" />
        <x-ui.select wire:model.live="roleFilter" label="{{ __('Role') }}"><option value="">{{ __('All roles') }}</option>@foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->localizedName() }}</option>@endforeach</x-ui.select>
        <x-ui.select wire:model.live="statusFilter" label="{{ __('Status') }}"><option value="">{{ __('All statuses') }}</option><option value="active">{{ __('Active') }}</option><option value="inactive">{{ __('Inactive') }}</option></x-ui.select>
        <div class="flex items-end"><x-ui.button wire:click="resetFilters">{{ __('Reset filters') }}</x-ui.button></div>
    </div>
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-start text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900"><tr>@foreach(['User', 'Roles', 'Status', 'Actions'] as $heading)<th scope="col" class="px-4 py-3 font-medium">{{ __($heading) }}</th>@endforeach</tr></thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($users as $user)
                    <tr wire:key="user-{{ $user->id }}">
                        <td class="px-4 py-4"><a class="font-medium underline-offset-4 hover:underline" href="{{ route('users.show', $user) }}" wire:navigate>{{ $user->name }}</a><p class="text-zinc-500">{{ $user->email }}</p></td>
                        <td class="px-4 py-4">{{ $user->roles->map(fn ($role) => $role->localizedName())->join(', ') ?: __('No roles') }}</td>
                        <td class="px-4 py-4"><x-ui.badge :color="$user->status === 'active' ? 'green' : 'zinc'">{{ __(ucfirst($user->status)) }}</x-ui.badge></td>
                        <td class="px-4 py-4"><div class="flex gap-2">
                            @can('update', $user)
                                <x-ui.button size="sm" :href="route('users.edit', $user)" wire:navigate>{{ __('Edit') }}</x-ui.button>
                                @if(auth()->id() !== $user->id)<x-ui.button size="sm" wire:click="toggleStatus({{ $user->id }})" wire:loading.attr="disabled">{{ $user->status === 'active' ? __('Deactivate') : __('Activate') }}</x-ui.button>@endif
                            @endcan
                            @can('delete', $user)<x-ui.button size="sm" variant="danger" wire:click="confirmDelete({{ $user->id }})">{{ __('Delete') }}</x-ui.button>@endcan
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-12 text-center text-zinc-500">{{ __('No users match your filters.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $users->links() }}
    <flux:modal wire:model="confirmingDelete" class="max-w-md">
        <div class="space-y-5"><flux:heading size="lg">{{ __('Delete user?') }}</flux:heading><flux:text>{{ __('This permanently removes the account and its role assignments.') }}</flux:text><flux:error name="status" />
        <div class="flex justify-end gap-2"><flux:modal.close><x-ui.button>{{ __('Cancel') }}</x-ui.button></flux:modal.close><x-ui.button variant="danger" wire:click="delete" wire:loading.attr="disabled">{{ __('Delete user') }}</x-ui.button></div></div>
    </flux:modal>
</div>
