<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/** Reads config/permissions.php, the single declaration of permissions, and applies it to the database. */
final class PermissionCatalog
{
    public const GUARD = 'api';

    /** @return array<string, string> name => label */
    public static function all(): array
    {
        $all = [];

        foreach (config('permissions.modules') as $module => $actions) {
            foreach ($actions as $action => $label) {
                $all["{$module}.{$action}"] = $label;
            }
        }

        return $all;
    }

    /** @return Collection<int, string> */
    public static function names(): Collection
    {
        return collect(array_keys(self::all()));
    }

    /**
     * Creates missing permissions and grants the configured defaults. With $prune, deletes permissions that are no longer declared.
     *
     * @return array{created: list<string>, stale: list<string>, pruned: bool}
     */
    public static function sync(bool $prune = false): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $existing = Permission::query()->where('guard_name', self::GUARD)->pluck('name');
        $created = self::names()->diff($existing)->values();

        foreach ($created as $name) {
            Permission::findOrCreate($name, self::GUARD);
        }

        $stale = $existing->diff(self::names())->values();

        if ($prune && $stale->isNotEmpty()) {
            Permission::query()->where('guard_name', self::GUARD)->whereIn('name', $stale)->get()->each->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (config('permissions.grants') as $role => $names) {
            Role::findOrCreate($role, self::GUARD)->givePermissionTo($names);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return ['created' => $created->all(), 'stale' => $stale->all(), 'pruned' => $prune];
    }
}
