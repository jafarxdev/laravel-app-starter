<?php

namespace App\Actions;

use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ProtectUserAccess
{
    /**
     * Call inside the same transaction as the mutation. The shared role row serializes
     * all changes that could remove the final active super administrator.
     *
     * @param  list<int|string>  $roleIds
     */
    public function handle(User $user, string $status, array $roleIds, bool $deleting = false, bool $selfService = false): void
    {
        $superRole = Role::where('slug', 'super-admin')->lockForUpdate()->first();
        $current = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
        $currentIds = $current->roles()->pluck('roles.id')->map(fn ($id): int => (int) $id)->sort()->values()->all();
        $nextIds = collect($roleIds)->map(fn ($id): int => (int) $id)->sort()->values()->all();

        if (! $selfService && auth()->id() === $user->id) {
            if ($deleting || $status !== $current->status || $currentIds !== $nextIds) {
                throw ValidationException::withMessages(['status' => __('You cannot delete, deactivate or change roles on your own account here.')]);
            }
        }

        if ($superRole && $current->status === 'active' && in_array($superRole->id, $currentIds, true)) {
            $removesAccess = $deleting || $status !== 'active' || ! in_array($superRole->id, $nextIds, true);

            if ($removesAccess && ! $superRole->users()->where('status', 'active')->where('users.id', '!=', $user->id)->exists()) {
                throw ValidationException::withMessages(['status' => __('The last active Super Admin must be preserved.')]);
            }
        }
    }
}
