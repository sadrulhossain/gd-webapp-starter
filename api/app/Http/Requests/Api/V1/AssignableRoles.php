<?php

namespace App\Http\Requests\Api\V1;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

/** Rules for a `roles` list: never the superadmin role, and a non-superadmin only roles whose permissions they all hold. */
final class AssignableRoles
{
    /** @return array<string, mixed> */
    public static function rules(?User $actor): array
    {
        return [
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', Rule::in(self::names($actor)->all())],
        ];
    }

    /** @return Collection<int, string> */
    public static function names(?User $actor): Collection
    {
        $held = $actor?->isSuperadmin() ? null : ($actor?->getAllPermissions()->pluck('name') ?? collect());

        return Role::query()->where('guard_name', 'api')->where('name', '!=', config('access.superadmin_role'))->with('permissions')->get()
            ->filter(fn (Role $role) => $held === null || $role->permissions->pluck('name')->diff($held)->isEmpty())
            ->pluck('name');
    }
}
