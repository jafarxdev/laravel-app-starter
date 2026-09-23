<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('roles.view');
    }

    public function create(User $user): bool
    {
        return $user->can('roles.create');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('roles.update') && $this->canManage($user, $role);
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can('roles.delete') && $this->canManage($user, $role)
            && (! $role->exists || ! $user->hasRole($role->slug));
    }

    public function assignPermissions(User $user, Role $role): bool
    {
        return $user->can('roles.assign-permissions') && $this->canManage($user, $role)
            && (! $role->exists || ! $user->hasRole($role->slug));
    }

    private function canManage(User $user, Role $role): bool
    {
        if ($role->is_system || $role->slug === 'super-admin') {
            return false;
        }

        $role->loadMissing('permissions');

        return $role->permissions->every(fn ($permission): bool => $user->hasPermission($permission->slug));
    }
}
