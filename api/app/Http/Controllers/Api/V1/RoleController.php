<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreRoleRequest;
use App\Http\Requests\Api\V1\UpdateRoleRequest;
use App\Http\Resources\Api\V1\RoleResource;
use App\Support\Audit;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * List roles
     *
     * Every role except the superadmin, with its permission names. Not paginated.
     */
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Role::class);

        return RoleResource::collection(
            Role::query()->where('guard_name', 'api')->where('name', '!=', config('access.superadmin_role'))->with('permissions')->withCount('users')->orderBy('name')->get(),
        );
    }

    /** Show a role */
    public function show(Role $role): RoleResource
    {
        Gate::authorize('view', $role);

        return new RoleResource($role->load('permissions')->loadCount('users'));
    }

    /** Create a role */
    public function store(StoreRoleRequest $request): RoleResource
    {
        /** @var Role $role */
        $role = Role::create(['name' => $request->validated('name'), 'guard_name' => 'api']);
        $role->syncPermissions($request->validated('permissions', []));
        Audit::record('created', $role, [], ['name' => $role->name, 'permissions' => $role->permissions->pluck('name')->sort()->values()->all()]);

        return new RoleResource($role->load('permissions'));
    }

    /**
     * Update a role
     *
     * `permissions` replaces the role's permission names. A permission you do not hold yourself is rejected (422),
     * and one the role already has that you do not hold is kept. A seeded system role cannot be renamed.
     */
    public function update(UpdateRoleRequest $request, Role $role): RoleResource
    {
        $before = ['name' => $role->name, 'permissions' => $role->permissions->pluck('name')->sort()->values()->all()];

        if ($request->has('name')) {
            $role->update(['name' => $request->validated('name')]);
        }

        if ($request->has('permissions')) {
            $actor = $request->user();
            $keep = $actor->isSuperadmin()
                ? []
                : $role->permissions->pluck('name')->diff($actor->getAllPermissions()->pluck('name'))->all();

            $role->syncPermissions([...$request->validated('permissions'), ...$keep]);
        }

        $after = ['name' => $role->name, 'permissions' => $role->load('permissions')->permissions->pluck('name')->sort()->values()->all()];

        if ($before !== $after) {
            Audit::record('updated', $role, $before, $after);
        }

        return new RoleResource($role->load('permissions')->loadCount('users'));
    }

    /**
     * Delete a role
     *
     * Refused (409) while users still hold the role; a seeded system role is never deletable (403).
     */
    public function destroy(Role $role): Response
    {
        Gate::authorize('delete', $role);
        abort_if($role->users()->exists(), 409, 'This role is still assigned to users.');
        $role->delete();
        Audit::record('deleted', $role, ['name' => $role->name]);

        return response()->noContent();
    }
}
