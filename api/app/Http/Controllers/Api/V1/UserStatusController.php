<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateUserStatusRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;

class UserStatusController extends Controller
{
    /**
     * Suspend or reactivate a user
     *
     * `status` is `active` or `suspended`. Suspending signs the user out everywhere and blocks sign-in. You cannot
     * suspend yourself, and a superadmin is never suspended.
     */
    public function __invoke(UpdateUserStatusRequest $request, User $user): UserResource
    {
        if ($request->validated('status') === 'suspended') {
            $user->forceFill(['suspended_at' => $user->suspended_at ?? now()])->save();
            $user->revokeSessions();
        } else {
            $user->forceFill(['suspended_at' => null])->save();
        }

        return new UserResource($user->load('roles'));
    }
}
