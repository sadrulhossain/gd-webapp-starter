<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('users.view');
    }

    public function view(User $user, User $target): bool
    {
        return $user->checkPermissionTo('users.view');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('users.create');
    }

    /** Only a superadmin may change a superadmin account. */
    public function update(User $user, User $target): bool
    {
        return $user->checkPermissionTo('users.update') && ($user->isSuperadmin() || ! $target->isSuperadmin());
    }

    /** Nobody suspends themselves; a superadmin is never suspended. */
    public function suspend(User $user, User $target): bool
    {
        return ($user->isSuperadmin() || $user->checkPermissionTo('users.update')) && $user->isNot($target) && ! $target->isSuperadmin();
    }

    /** Sessions of another account follow the update rule. */
    public function manageSessions(User $user, User $target): bool
    {
        return $this->update($user, $target);
    }

    /** Nobody deletes their own account; a superadmin is never deleted. */
    public function delete(User $user, User $target): bool
    {
        return ($user->isSuperadmin() || $user->checkPermissionTo('users.delete'))
            && $user->isNot($target)
            && ! $target->isSuperadmin();
    }
}
