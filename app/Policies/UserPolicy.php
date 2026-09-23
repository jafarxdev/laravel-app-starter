<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('users.view');
    }

    public function view(User $actor, User $user): bool
    {
        return $actor->can('users.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('users.create');
    }

    public function update(User $actor, User $user): bool
    {
        return $actor->can('users.update')
            && $this->canManage($actor, $user);
    }

    public function delete(User $actor, User $user): bool
    {
        return $actor->can('users.delete')
            && ! $actor->is($user)
            && $this->canManage($actor, $user);
    }

    public function assignRoles(User $actor, User $user): bool
    {
        return $actor->can('users.assign-roles')
            && $this->canManage($actor, $user);
    }

    private function canManage(User $actor, User $user): bool
    {
        if ($actor->hasRole('super-admin')) {
            return true;
        }

        if ($user->hasRole('super-admin')) {
            return false;
        }

        $user->loadMissing('roles.permissions');

        return $user->roles->flatMap->permissions->every(fn ($permission): bool => $actor->hasPermission($permission->slug));
    }
}
