<?php

use App\Models\Permission;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $moduleFilter = '';
    public bool $showForm = false;
    public bool $confirmingDelete = false;
    #[Locked]
    public ?int $editingId = null;
    #[Locked]
    public ?int $deletingId = null;
    public string $name = '';
    public string $slug = '';
    public string $module = '';
    public string $description = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Permission::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'moduleFilter'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'moduleFilter');
        $this->resetPage();
    }

    public function create(): void
    {
        Gate::authorize('create', Permission::class);
        $this->reset('editingId', 'name', 'slug', 'module', 'description');
        $this->resetValidation();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $permission = Permission::findOrFail($id);
        Gate::authorize('update', $permission);
        $this->resetValidation();
        $this->editingId = $id;
        $this->name = $permission->name;
        $this->slug = $permission->slug;
        $this->module = $permission->module;
        $this->description = $permission->description ?? '';
        $this->showForm = true;
    }

    public function save(): void
    {
        $permission = $this->editingId ? Permission::findOrFail($this->editingId) : new Permission;
        Gate::authorize($permission->exists ? 'update' : 'create', $permission->exists ? $permission : Permission::class);
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'module' => ['required', 'max:100', 'regex:/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/'],
            'slug' => ['required', 'max:200', 'regex:/^[a-z][a-z0-9-]*\.[a-z][a-z0-9-]*$/', Rule::unique('permissions')->ignore($permission)],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        if (strtok($data['slug'], '.') !== $data['module']) {
            $this->addError('slug', 'The slug must start with the selected module followed by a dot.');
            return;
        }

        if ($permission->exists && $permission->slug !== $data['slug'] && $permission->roles()->exists()) {
            abort_unless(auth()->user()->hasRole('super-admin'), 403);
        }

        $permission->fill($data)->save();
        auth()->user()->unsetRelation('roles');
        $this->showForm = false;
        session()->flash('success', 'Permission saved.');
    }

    public function confirmDelete(int $id): void
    {
        Gate::authorize('delete', Permission::findOrFail($id));
        $this->deletingId = $id;
        $this->confirmingDelete = true;
    }

    public function delete(): void
    {
        $permission = Permission::findOrFail($this->deletingId);
        Gate::authorize('delete', $permission);
        $permission->delete();
        auth()->user()->unsetRelation('roles');
        $this->reset('confirmingDelete', 'deletingId');
        session()->flash('success', 'Permission deleted.');
    }

    public function with(): array
    {
        Gate::authorize('viewAny', Permission::class);

        return [
            'permissions' => Permission::query()
                ->when($this->search !== '', fn ($query) => $query->where(fn ($query) => $query
                    ->where('name', 'like', '%'.$this->search.'%')->orWhere('slug', 'like', '%'.$this->search.'%')))
                ->when($this->moduleFilter !== '', fn ($query) => $query->where('module', $this->moduleFilter))
                ->orderBy('module')->orderBy('slug')->paginate(12),
            'modules' => Permission::select('module')->distinct()->orderBy('module')->pluck('module'),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4"><div><flux:heading size="xl">Permissions</flux:heading><flux:text>Define application capabilities using module.action names.</flux:text></div>@can('create', App\Models\Permission::class)<x-ui.button variant="primary" wire:click="create">Create permission</x-ui.button>@endcan</div>
    @if(session('success'))<flux:callout variant="success">{{ session('success') }}</flux:callout>@endif
    <div class="grid items-end gap-3 sm:grid-cols-3">
        <x-ui.input wire:model.live.debounce.300ms="search" label="Search permissions" placeholder="Name or slug" />
        <x-ui.select wire:model.live="moduleFilter" label="Module"><option value="">All modules</option>@foreach($modules as $moduleName)<option value="{{ $moduleName }}">{{ $moduleName }}</option>@endforeach</x-ui.select>
        <x-ui.button wire:click="resetFilters">Reset filters</x-ui.button>
    </div>
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700"><table class="w-full text-left text-sm">
        <thead class="bg-zinc-50 dark:bg-zinc-900"><tr>@foreach(['Permission', 'Module', 'Actions'] as $heading)<th scope="col" class="px-4 py-3">{{ $heading }}</th>@endforeach</tr></thead>
        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">@forelse($permissions as $permission)<tr wire:key="permission-{{ $permission->id }}">
            <td class="px-4 py-4"><p class="font-medium">{{ $permission->name }}</p><p class="text-zinc-500">{{ $permission->slug }}</p></td><td class="px-4 py-4">{{ $permission->module }}</td>
            <td class="px-4 py-4"><div class="flex gap-2">@if($permission->isCritical())<x-ui.badge>Protected</x-ui.badge>@endif @can('update', $permission)<x-ui.button size="sm" wire:click="edit({{ $permission->id }})">Edit</x-ui.button>@endcan @can('delete', $permission)<x-ui.button size="sm" variant="danger" wire:click="confirmDelete({{ $permission->id }})">Delete</x-ui.button>@endcan</div></td>
        </tr>@empty<tr><td colspan="3" class="p-12 text-center text-zinc-500">No permissions found.</td></tr>@endforelse</tbody>
    </table></div>
    {{ $permissions->links() }}
    <flux:modal wire:model="showForm" class="w-full max-w-lg">
        <form wire:submit="save" class="space-y-5"><flux:heading size="lg">{{ $editingId ? 'Edit permission' : 'Create permission' }}</flux:heading>
            <x-ui.input wire:model="name" label="Name" required />
            <x-ui.input wire:model="module" label="Module" placeholder="records" required />
            <x-ui.input wire:model="slug" label="Slug" placeholder="records.view" required />
            <x-ui.textarea wire:model="description" label="Description" />
            <div class="flex justify-end gap-3"><flux:modal.close><x-ui.button>Cancel</x-ui.button></flux:modal.close><x-ui.button type="submit" variant="primary" wire:loading.attr="disabled">Save permission</x-ui.button></div>
        </form>
    </flux:modal>
    <flux:modal wire:model="confirmingDelete" class="max-w-md"><div class="space-y-5"><flux:heading size="lg">Delete permission?</flux:heading><flux:text>This removes the permission from every role. Existing routes using it will deny access.</flux:text><div class="flex justify-end gap-3"><flux:modal.close><x-ui.button>Cancel</x-ui.button></flux:modal.close><x-ui.button variant="danger" wire:click="delete" wire:loading.attr="disabled">Delete permission</x-ui.button></div></div></flux:modal>
</div>
