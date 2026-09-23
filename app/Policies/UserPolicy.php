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
            && (! $user->hasRole('super-admin') || $actor->hasRole('super-admin'));
    }

    public function delete(User $actor, User $user): bool
    {
        return $actor->can('users.delete')
            && ! $actor->is($user)
            && (! $user->hasRole('super-admin') || $actor->hasRole('super-admin'));
    }

    public function assignRoles(User $actor, User $user): bool
    {
        return $actor->can('users.assign-roles')
            && (! $user->hasRole('super-admin') || $actor->hasRole('super-admin'));
    }
}
