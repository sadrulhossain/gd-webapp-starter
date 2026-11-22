<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AssignableRoles;
use App\Http\Requests\Api\V1\StoreUserRequest;
use App\Http\Requests\Api\V1\UpdateUserRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    /**
     * List users
     *
     * Paginated, 15 per page, oldest first.
     */
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', User::class);

        return UserResource::collection(User::query()->with('roles')->orderBy('id')->paginate(15));
    }

    /** Show a user */
    public function show(User $user): UserResource
    {
        Gate::authorize('view', $user);

        return new UserResource($user->load('roles'));
    }

    /** Create a user */
    public function store(StoreUserRequest $request): UserResource
    {
        $data = $request->validated();
        $user = User::query()->create(collect($data)->only(['name', 'email', 'password'])->all());
        $user->syncRoles($data['roles'] ?? []);
        Audit::record('roles_changed', $user, [], ['roles' => $user->roles->pluck('name')->sort()->values()->all()]);

        return new UserResource($user->load('roles'));
    }

    /**
     * Update a user
     *
     * A blank password keeps the current one. `roles` replaces the user's roles (roles you may not assign, such as the superadmin role, are kept as they are); a new password signs the user out everywhere.
     */
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $data = $request->validated();
        $fields = collect($data)->only(['name', 'email', 'password'])->all();

        if (($fields['password'] ?? null) === null) {
            unset($fields['password']);
        }

        $user->update($fields);

        if (isset($fields['password'])) {
            $user->revokeSessions();
        }

        if (array_key_exists('roles', $data)) {
            // Roles the actor may not assign (the superadmin role, roles above their own permissions) stay as they are.
            $keep = $user->roles->pluck('name')->diff(AssignableRoles::names($request->user()))->all();
            $before = $user->roles->pluck('name')->sort()->values()->all();
            $user->syncRoles([...$data['roles'], ...$keep]);
            $after = $user->load('roles')->roles->pluck('name')->sort()->values()->all();

            if ($before !== $after) {
                Audit::record('roles_changed', $user, ['roles' => $before], ['roles' => $after]);
            }
        }

        return new UserResource($user->load('roles'));
    }

    /** Delete a user (never yourself) */
    public function destroy(User $user): Response
    {
        Gate::authorize('delete', $user);
        $user->tokens()->delete();
        $user->delete();

        return response()->noContent();
    }
}
