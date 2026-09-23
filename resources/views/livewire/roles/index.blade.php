<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public bool $showForm = false;
    public bool $confirmingDelete = false;
    #[Locked]
    public ?int $editingId = null;
    #[Locked]
    public ?int $deletingId = null;
    #[Locked]
    public string $deletingSlug = '';
    #[Locked]
    public int $assignedUsers = 0;
    public string $confirmation = '';
    public string $name = '';
    public string $slug = '';
    public string $description = '';
    public array $selectedPermissions = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', Role::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        Gate::authorize('create', Role::class);
        $this->reset('editingId', 'name', 'slug', 'description', 'selectedPermissions');
        $this->resetValidation();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $role = Role::findOrFail($id);
        Gate::authorize('update', $role);
        $this->resetValidation();
        $this->editingId = $id;
        $this->name = $role->name;
        $this->slug = $role->slug;
        $this->description = $role->description ?? '';
        $this->selectedPermissions = $role->permissions()->pluck('permissions.id')->map(fn ($id): string => (string) $id)->all();
        $this->showForm = true;
    }

    public function save(): void
    {
        $role = $this->editingId ? Role::findOrFail($this->editingId) : new Role;
        Gate::authorize($role->exists ? 'update' : 'create', $role->exists ? $role : Role::class);
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'max:100', 'regex:/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/', Rule::notIn(['super-admin']), Rule::unique('roles')->ignore($role)],
            'description' => ['nullable', 'string', 'max:1000'],
            'selectedPermissions' => ['array'],
            'selectedPermissions.*' => ['integer', 'distinct', Rule::exists('permissions', 'id')],
        ]);
        $current = $role->exists ? $role->permissions()->pluck('permissions.id')->map(fn ($id): int => (int) $id)->sort()->values()->all() : [];
        $selected = collect($this->selectedPermissions)->map(fn ($id): int => (int) $id)->sort()->values()->all();

        if ($current !== $selected) {
            Gate::authorize('assignPermissions', $role);
            foreach (Permission::whereIn('id', $selected)->get() as $permission) {
                Gate::authorize($permission->slug);
            }
        }

        DB::transaction(function () use ($role, $data, $current, $selected): void {
            $role->fill(collect($data)->only(['name', 'slug', 'description'])->all())->save();
            if ($current !== $selected) {
                $role->permissions()->sync($selected);
                \App\Models\AuditLog::record('role.permissions-assigned', $role, ['permission_ids' => $current], ['permission_ids' => $selected]);
            }
        });
        auth()->user()->unsetRelation('roles');
        $this->showForm = false;
        session()->flash('success', 'Role saved.');
    }

    public function confirmDelete(int $id): void
    {
        $role = Role::findOrFail($id);
        Gate::authorize('delete', $role);
        $this->resetValidation();
        $this->deletingId = $id;
        $this->deletingSlug = $role->slug;
        $this->assignedUsers = $role->users()->count();
        $this->confirmation = '';
        $this->confirmingDelete = true;
    }

    public function delete(): void
    {
        $role = Role::findOrFail($this->deletingId);
        Gate::authorize('delete', $role);
        $this->validate(['confirmation' => ['required', Rule::in([$role->slug])]]);
        $role->delete();
        $this->reset('confirmingDelete', 'deletingId', 'confirmation');
        session()->flash('success', 'Role deleted. User accounts were preserved.');
    }

    public function with(): array
    {
        Gate::authorize('viewAny', Role::class);

        return [
            'roles' => Role::with('permissions')->withCount('users')
                ->when($this->search !== '', fn ($query) => $query->where(fn ($query) => $query
                    ->where('name', 'like', '%'.$this->search.'%')->orWhere('slug', 'like', '%'.$this->search.'%')))
                ->orderBy('name')->orderBy('id')->paginate(10),
            'permissionGroups' => auth()->user()->can('roles.assign-permissions')
                ? Permission::orderBy('module')->orderBy('slug')->get()->groupBy('module') : collect(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4"><div><flux:heading size="xl">Roles</flux:heading><flux:text>Group permissions into reusable responsibilities.</flux:text></div>@can('create', App\Models\Role::class)<x-ui.button variant="primary" wire:click="create">Create role</x-ui.button>@endcan</div>
    <x-ui.flash-messages />
    <x-ui.input wire:model.live.debounce.300ms="search" label="Search roles" placeholder="Name or slug" class="max-w-md" />
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700"><table class="w-full text-left text-sm">
        <thead class="bg-zinc-50 dark:bg-zinc-900"><tr>@foreach(['Role', 'Permissions', 'Users', 'Actions'] as $heading)<th scope="col" class="px-4 py-3">{{ $heading }}</th>@endforeach</tr></thead>
        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">@forelse($roles as $role)<tr wire:key="role-{{ $role->id }}">
            <td class="px-4 py-4"><p class="font-medium">{{ $role->name }} @if($role->is_system)<x-ui.badge size="sm">System</x-ui.badge>@endif</p><p class="text-zinc-500">{{ $role->slug }}</p></td>
            <td class="px-4 py-4">{{ $role->permissions->count() }}</td><td class="px-4 py-4">{{ $role->users_count }}</td>
            <td class="px-4 py-4"><div class="flex gap-2">@can('update', $role)<x-ui.button size="sm" wire:click="edit({{ $role->id }})">Edit</x-ui.button>@endcan @can('delete', $role)<x-ui.button size="sm" variant="danger" wire:click="confirmDelete({{ $role->id }})">Delete</x-ui.button>@endcan</div></td>
        </tr>@empty<tr><td colspan="4" class="p-12 text-center text-zinc-500">No roles found.</td></tr>@endforelse</tbody>
    </table></div>
    {{ $roles->links() }}
    <flux:modal wire:model="showForm" class="w-full max-w-2xl">
        <form wire:submit="save" class="space-y-5"><flux:heading size="lg">{{ $editingId ? 'Edit role' : 'Create role' }}</flux:heading>
            <x-ui.input wire:model="name" label="Name" required />
            <x-ui.input wire:model="slug" label="Slug" placeholder="record-manager" required />
            <x-ui.textarea wire:model="description" label="Description" />
            @can('roles.assign-permissions')
                <div class="space-y-4">@foreach($permissionGroups as $module => $permissions)<fieldset class="space-y-2 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700"><legend class="px-2 font-medium">{{ ucfirst($module) }}</legend><div class="grid gap-2 sm:grid-cols-2">@foreach($permissions as $permission)@can($permission->slug)<x-ui.checkbox wire:model="selectedPermissions" :value="(string) $permission->id" :label="$permission->slug" />@endcan @endforeach</div></fieldset>@endforeach</div>
                <flux:error name="selectedPermissions" /><flux:error name="selectedPermissions.*" />
            @endcan
            <div class="flex justify-end gap-3"><flux:modal.close><x-ui.button>Cancel</x-ui.button></flux:modal.close><x-ui.button type="submit" variant="primary" wire:loading.attr="disabled">Save role</x-ui.button></div>
        </form>
    </flux:modal>
    <flux:modal wire:model="confirmingDelete" class="max-w-md">
        <form wire:submit="delete" class="space-y-5"><flux:heading size="lg">Delete role?</flux:heading><flux:text>{{ $assignedUsers }} user(s) will lose this role. Their accounts will remain. Type <strong>{{ $deletingSlug }}</strong> to confirm.</flux:text><x-ui.input wire:model="confirmation" label="Role slug confirmation" />
        <div class="flex justify-end gap-3"><flux:modal.close><x-ui.button>Cancel</x-ui.button></flux:modal.close><x-ui.button type="submit" variant="danger" wire:loading.attr="disabled">Delete role</x-ui.button></div></form>
    </flux:modal>
</div>
