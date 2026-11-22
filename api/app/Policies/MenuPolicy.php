<?php

namespace App\Policies;

use App\Models\Menu;
use App\Models\User;

class MenuPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('menus.view');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('menus.create');
    }

    public function delete(User $user, ?Menu $menu = null): bool
    {
        return $user->checkPermissionTo('menus.delete');
    }

    public function update(User $user, ?Menu $menu = null): bool
    {
        return $user->checkPermissionTo('menus.update');
    }
}
