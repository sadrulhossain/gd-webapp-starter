<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements OAuthenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /** Roles and permissions are stored for the Passport `api` guard. */
    protected string $guard_name = 'api';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'suspended_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /** Revokes every access token (and its refresh tokens), optionally keeping one. */
    public function revokeSessions(?string $exceptTokenId = null): void
    {
        $ids = $this->tokens()->when($exceptTokenId, fn ($q) => $q->where('id', '!=', $exceptTokenId))->pluck('id');

        RefreshToken::query()->whereIn('access_token_id', $ids)->update(['revoked' => true]);
        Token::query()->whereIn('id', $ids)->update(['revoked' => true]);
    }

    public function isSuperadmin(): bool
    {
        return $this->hasRole(config('access.superadmin_role'));
    }
}
