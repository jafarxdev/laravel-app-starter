<?php

use App\Actions\ProtectUserAccess;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new class extends Component {
    #[Locked]
    public ?int $userId = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $status = 'active';
    public array $selectedRoles = [];

    public function mount(?User $user = null): void
    {
        if ($user?->exists) {
            Gate::authorize('update', $user);
            $this->userId = $user->id;
            $this->fill($user->only('name', 'email', 'status'));
            $this->selectedRoles = $user->roles()->pluck('roles.id')->map(fn ($id): string => (string) $id)->all();
        } else {
            Gate::authorize('create', User::class);
        }
    }

    public function save(ProtectUserAccess $protection): void
    {
        $user = $this->userId ? User::findOrFail($this->userId) : new User;
        Gate::authorize($user->exists ? 'update' : 'create', $user->exists ? $user : User::class);
        $this->email = strtolower(trim($this->email));
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'password' => [$user->exists ? 'nullable' : 'required', 'confirmed', Password::defaults()],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'selectedRoles' => ['array'],
            'selectedRoles.*' => ['integer', 'distinct', Rule::exists('roles', 'id')],
        ]);

        $currentRoles = $user->exists ? $user->roles()->pluck('roles.id')->map(fn ($id): int => (int) $id)->sort()->values()->all() : [];
        $selectedRoles = collect($this->selectedRoles)->map(fn ($id): int => (int) $id)->sort()->values()->all();
        $rolesChanged = $currentRoles !== $selectedRoles;

        if ($rolesChanged) {
            Gate::authorize('assignRoles', $user);
            foreach (Role::with('permissions')->whereIn('id', $selectedRoles)->get() as $role) {
                abort_if($role->slug === 'super-admin' && ! auth()->user()->hasRole('super-admin'), 403);
                foreach ($role->permissions as $permission) {
                    Gate::authorize($permission->slug);
                }
            }
        }

        DB::transaction(function () use ($user, $data, $selectedRoles, $rolesChanged, $protection): void {
            if ($user->exists) {
                $protection->handle($user, $data['status'], $selectedRoles);
            }
            $user->fill(collect($data)->only(['name', 'email', 'status'])->all());
            if ($this->password !== '') {
                $user->password = $this->password;
            }
            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }
            $user->save();
            if ($rolesChanged) {
                $user->syncRoles($selectedRoles);
            }
        });

        $this->reset('password', 'password_confirmation');
        session()->flash('success', 'User saved.');
        $this->redirectRoute('users.index', navigate: true);
    }

    public function with(): array
    {
        Gate::authorize($this->userId ? 'update' : 'create', $this->userId ? User::findOrFail($this->userId) : User::class);

        return ['roles' => Role::orderBy('name')->get()];
    }
}; ?>

<div class="mx-auto max-w-3xl space-y-6">
    <div><flux:heading size="xl">{{ $userId ? 'Edit user' : 'Create user' }}</flux:heading><flux:text>Account details and role assignments.</flux:text></div>
    <form wire:submit="save" class="space-y-6 rounded-xl border border-zinc-200 p-6 dark:border-zinc-700">
        <div class="grid gap-5 sm:grid-cols-2">
            <flux:input wire:model="name" label="Name" required autofocus autocomplete="name" />
            <flux:input wire:model="email" label="Email address" type="email" required autocomplete="email" />
            <flux:input wire:model="password" label="Password" type="password" autocomplete="new-password" :description="$userId ? 'Leave blank to keep the current password.' : 'At least 8 characters.'" />
            <flux:input wire:model="password_confirmation" label="Confirm password" type="password" autocomplete="new-password" />
        </div>
        <flux:select wire:model="status" label="Status" :disabled="$userId === auth()->id()"><option value="active">Active</option><option value="inactive">Inactive</option></flux:select>
        @can('users.assign-roles')
            <fieldset class="space-y-3"><legend class="font-medium">Roles</legend>
                @foreach($roles as $role)
                    @if($role->slug !== 'super-admin' || auth()->user()->hasRole('super-admin'))
                        <flux:checkbox wire:model="selectedRoles" :value="(string) $role->id" :label="$role->name" :disabled="$userId === auth()->id()" />
                    @endif
                @endforeach
                <flux:error name="selectedRoles" /><flux:error name="selectedRoles.*" />
            </fieldset>
        @endcan
        <div class="flex justify-end gap-3"><flux:button :href="route('users.index')" wire:navigate>Cancel</flux:button><flux:button type="submit" variant="primary" wire:loading.attr="disabled">Save user</flux:button></div>
    </form>
</div>
