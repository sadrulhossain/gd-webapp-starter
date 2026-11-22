<?php

use App\Models\User;
use Database\Seeders\OauthClientSeeder;
use Illuminate\Support\Str;
use Laravel\Passport\Token;
use Spatie\Permission\Models\Role;

const SESSION_CLIENT = '019e0000-0000-7000-8000-000000000001';

function sessionFor(User $user, bool $revoked = false, ?DateTimeInterface $expires = null): Token
{
    config(['access.oauth_admin_client' => ['id' => SESSION_CLIENT, 'redirect_uri' => 'http://localhost/cb']]);
    (new OauthClientSeeder)->run();

    return Token::forceCreate([
        'id' => Str::random(80), 'user_id' => $user->id, 'client_id' => SESSION_CLIENT, 'scopes' => '[]',
        'revoked' => $revoked, 'expires_at' => $expires ?? now()->addHour(),
    ]);
}

it('requires authentication and users.update to change status', function () {
    $target = User::factory()->create();
    $this->patchJson("/api/v1/users/{$target->id}/status", ['status' => 'suspended'])->assertUnauthorized();

    actingAsRole('viewer', ['users.view']);
    $this->patchJson("/api/v1/users/{$target->id}/status", ['status' => 'suspended'])->assertForbidden();
});

it('suspends and reactivates a user and signs them out', function () {
    actingAsRole('manager', ['users.update']);
    $target = User::factory()->create();
    $token = sessionFor($target);

    $this->patchJson("/api/v1/users/{$target->id}/status", ['status' => 'suspended'])
        ->assertOk()->assertJsonPath('data.status', 'suspended');
    expect($target->refresh()->isSuspended())->toBeTrue()->and($token->refresh()->revoked)->toBeTrue();

    $this->patchJson("/api/v1/users/{$target->id}/status", ['status' => 'active'])
        ->assertOk()->assertJsonPath('data.status', 'active');
    expect($target->refresh()->isSuspended())->toBeFalse();
});

it('validates the status value', function () {
    actingAsRole('manager', ['users.update']);
    $target = User::factory()->create();

    $this->patchJson("/api/v1/users/{$target->id}/status", ['status' => 'gone'])
        ->assertUnprocessable()->assertJsonStructure(['error' => ['details' => ['status']]]);
});

it('never suspends yourself or a superadmin', function () {
    $me = actingAsRole('manager', ['users.update']);
    $root = User::factory()->create()->assignRole(Role::findOrCreate(config('access.superadmin_role'), 'api'));

    $this->patchJson("/api/v1/users/{$me->id}/status", ['status' => 'suspended'])->assertForbidden();
    $this->patchJson("/api/v1/users/{$root->id}/status", ['status' => 'suspended'])->assertForbidden();
});

it('never deletes a superadmin, not even by another superadmin', function () {
    $actor = actingAsRole('manager', ['users.delete']);
    $actor->assignRole(Role::findOrCreate(config('access.superadmin_role'), 'api'));
    $root = User::factory()->create()->assignRole(config('access.superadmin_role'));

    $this->deleteJson("/api/v1/users/{$root->id}")->assertForbidden();
});

it('refuses an authenticated request from a suspended account', function () {
    $user = actingAsRole('viewer', ['users.view']);
    $user->forceFill(['suspended_at' => now()])->save();

    $this->getJson('/api/v1/me')->assertForbidden();
});

it('does not sign a suspended user in on the login page', function () {
    $user = User::factory()->create(['suspended_at' => now()]);

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
    $this->assertGuest('web');
});

it('revokes sessions when an admin sets a new password', function () {
    actingAsRole('manager', ['users.update']);
    $target = User::factory()->create();
    $token = sessionFor($target);

    $this->patchJson("/api/v1/users/{$target->id}", ['password' => 'a-brand-new-password'])->assertOk();

    expect($token->refresh()->revoked)->toBeTrue();
});

it('lists a user\'s live sessions and revokes them all', function () {
    actingAsRole('manager', ['users.view', 'users.update']);
    $target = User::factory()->create();
    $live = sessionFor($target);
    sessionFor($target, revoked: true);
    sessionFor($target, expires: now()->subMinute());

    $this->getJson("/api/v1/users/{$target->id}/sessions")
        ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $live->id);

    $this->deleteJson("/api/v1/users/{$target->id}/sessions")->assertNoContent();
    expect($live->refresh()->revoked)->toBeTrue();
});

it('guards the session endpoints by permission', function () {
    $target = User::factory()->create();
    $this->getJson("/api/v1/users/{$target->id}/sessions")->assertUnauthorized();

    actingAsRole('nobody');
    $this->getJson("/api/v1/users/{$target->id}/sessions")->assertForbidden();
    $this->deleteJson("/api/v1/users/{$target->id}/sessions")->assertForbidden();
});

it('lets a user list and revoke only their own sessions', function () {
    $me = actingAsRole('nobody');
    $mine = sessionFor($me);
    $other = sessionFor(User::factory()->create());

    $this->getJson('/api/v1/me/sessions')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $mine->id);
    $this->deleteJson("/api/v1/me/sessions/{$other->id}")->assertNotFound();
    $this->deleteJson("/api/v1/me/sessions/{$mine->id}")->assertNoContent();
    expect($mine->refresh()->revoked)->toBeTrue()->and($other->refresh()->revoked)->toBeFalse();
});
