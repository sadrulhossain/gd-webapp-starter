<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Passport\AccessToken;
use Laravel\Passport\Token;

class MeSessionController extends Controller
{
    /**
     * List own sessions
     *
     * Unrevoked, unexpired access tokens, newest first; `current` marks the one making this request.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $token = $user->token();

        return response()->json(['data' => self::describe($user, $token instanceof AccessToken ? $token->id : null)]);
    }

    /** Revoke one of own sessions */
    public function destroy(Request $request, string $session): Response
    {
        /** @var User $user */
        $user = $request->user();
        $token = $user->tokens()->whereKey($session)->firstOrFail();

        $token->refreshToken?->revoke();
        $token->revoke();

        return response()->noContent();
    }

    /** @return list<array<string, mixed>> */
    public static function describe(User $user, ?string $currentId): array
    {
        return $user->tokens()
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->get()
            ->map(fn (Token $t) => [
                'id' => $t->id,
                'created_at' => $t->created_at,
                'expires_at' => $t->expires_at,
                'current' => $t->id === $currentId,
            ])->all();
    }
}
