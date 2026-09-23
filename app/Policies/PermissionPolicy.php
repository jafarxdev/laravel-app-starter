<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;

class PermissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('permissions.view');
    }

    public function create(User $user): bool
    {
        return $user->can('permissions.create');
    }

    public function update(User $user, Permission $permission): bool
    {
        return $user->can('permissions.update') && ! $permission->isCritical();
    }

    public function delete(User $user, Permission $permission): bool
    {
        return $user->can('permissions.delete') && ! $permission->isCritical()
            && ($user->hasRole('super-admin') || ! $permission->roles()->exists());
    }
}
