<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\PermissionCatalog;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * List permissions
     *
     * `data` is every permission name (`<module>.<action>`). `groups` lists them per module with the label
     * declared in config/permissions.php, for the role matrix. The route is gated by `roles.view`.
     */
    public function __invoke(): JsonResponse
    {
        $labels = PermissionCatalog::all();
        $names = Permission::query()->where('guard_name', PermissionCatalog::GUARD)->orderBy('name')->pluck('name');

        return response()->json([
            'data' => $names,
            'groups' => $names->groupBy(fn (string $name) => explode('.', $name)[0])->map(
                fn ($group) => $group->map(fn (string $name) => ['name' => $name, 'label' => $labels[$name] ?? null])->values(),
            ),
        ]);
    }
}
