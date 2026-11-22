<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class UserSessionController extends Controller
{
    /**
     * List a user's active sessions
     *
     * Unrevoked, unexpired access tokens, newest first.
     */
    public function index(User $user): JsonResponse
    {
        Gate::authorize('view', $user);

        return response()->json(['data' => MeSessionController::describe($user, null)]);
    }

    /** Sign a user out everywhere */
    public function destroy(User $user): Response
    {
        Gate::authorize('manageSessions', $user);
        $user->revokeSessions();

        return response()->noContent();
    }
}
