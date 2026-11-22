<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    /**
     * Dashboard counts
     *
     * Row counts for every module the user may view. Modules the user cannot view are left out.
     */
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $counts = [];

        if ($user->can('users.view')) {
            $counts['users'] = User::query()->count();
        }

        if ($user->can('roles.view')) {
            $counts['roles'] = Role::query()->where('guard_name', 'api')->count();
        }

        return response()->json(['data' => $counts]);
    }
}
