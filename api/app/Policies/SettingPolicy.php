<?php

namespace App\Policies;

use App\Models\User;

/** Superadmin is handled by Gate::before. Use checkPermissionTo (false, not an exception, for unknown permissions). */
class SettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('settings.view');
    }

    public function update(User $user): bool
    {
        return $user->checkPermissionTo('settings.update');
    }
}
