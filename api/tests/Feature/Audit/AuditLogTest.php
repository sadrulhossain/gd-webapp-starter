<?php

use App\Models\AuditLog;
use App\Models\Menu;
use App\Models\User;
use App\Support\Audit;
use App\Support\Settings;
use Database\Seeders\AuditModuleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

it('requires authentication and audit.view', function () {
    $this->getJson('/api/v1/audit-logs')->assertUnauthorized();

    $entry = Audit::record('custom');

    actingAsRole('nobody');
    $this->getJson('/api/v1/audit-logs')->assertForbidden();
    $this->getJson("/api/v1/audit-logs/{$entry->id}")->assertForbidden();
});

it('logs model changes with the actor, and redacts the password', function () {
    $actor = actingAsRole('manager', ['users.create', 'users.update', 'audit.view']);

    $this->postJson('/api/v1/users', ['name' => 'Jo', 'email' => 'jo@example.test', 'password' => 'a-long-secret-1', 'password_confirmation' => 'a-long-secret-1'])->assertCreated();
    $jo = User::where('email', 'jo@example.test')->firstOrFail();
    $this->patchJson("/api/v1/users/{$jo->id}", ['name' => 'Joe', 'password' => 'another-long-one-2', 'password_confirmation' => 'another-long-one-2'])->assertOk();

    $created = AuditLog::where(['event' => 'created', 'auditable_type' => 'User', 'auditable_id' => (string) $jo->id])->firstOrFail();
    $updated = AuditLog::where(['event' => 'updated', 'auditable_id' => (string) $jo->id])->latest('id')->firstOrFail();

    expect($created->user_id)->toBe($actor->id)->and($created->new_values['password'])->toBe('[redacted]')
        ->and($updated->old_values['name'])->toBe('Jo')->and($updated->new_values['name'])->toBe('Joe')
        ->and($updated->new_values['password'])->toBe('[redacted]');

    $body = $this->getJson('/api/v1/audit-logs?auditable_type=User&event=updated')->assertOk()->getContent();
    expect($body)->not->toContain('another-long-one-2')->not->toContain('a-long-secret-1');
});

it('never stores a secret setting value', function () {
    config(['settings.settings.smtp_password' => ['type' => 'secret', 'default' => null, 'public' => false, 'rules' => ['nullable', 'string']]]);

    Settings::set(['smtp_password' => 'hunter2', 'tagline' => 'Hello']);
    Settings::set(['smtp_password' => null]);

    $all = json_encode(AuditLog::all()->toArray());
    expect($all)->not->toContain('hunter2')
        ->and(AuditLog::where(['auditable_type' => 'Setting', 'auditable_id' => 'tagline'])->exists())->toBeTrue()
        ->and(AuditLog::where(['event' => 'deleted', 'auditable_id' => 'smtp_password'])->exists())->toBeTrue();
});

it('logs role and menu changes that bypass model events', function () {
    actingAsRole('boss', ['roles.create', 'roles.update', 'roles.delete', 'menus.update', 'users.view']);

    $id = $this->postJson('/api/v1/roles', ['name' => 'editor', 'permissions' => ['users.view']])->assertSuccessful()->json('data.id');
    $this->patchJson("/api/v1/roles/{$id}", ['permissions' => []])->assertOk();
    $this->deleteJson("/api/v1/roles/{$id}")->assertNoContent();

    $events = AuditLog::where('auditable_type', 'Role')->orderBy('id')->pluck('event')->all();
    expect($events)->toBe(['created', 'updated', 'deleted']);
    expect(AuditLog::where('event', 'updated')->latest('id')->first()->old_values['permissions'])->toBe(['users.view']);

    $a = Menu::create(['title' => 'A']);
    $b = Menu::create(['title' => 'B']);
    $this->putJson('/api/v1/menus/reorder', ['ids' => [$b->id, $a->id]])->assertOk();
    expect(AuditLog::where('event', 'reordered')->first()->new_values['menu_ids'])->toBe([$b->id, $a->id]);
});

it('is append-only, in the model and in the database', function () {
    $entry = Audit::record('custom');

    expect(fn () => $entry->update(['event' => 'x']))->toThrow(LogicException::class)
        ->and(fn () => $entry->delete())->toThrow(LogicException::class)
        ->and(fn () => DB::table('audit_logs')->update(['event' => 'x']))->toThrow(QueryException::class)
        ->and(fn () => DB::table('audit_logs')->delete())->toThrow(QueryException::class);
});

it('filters and paginates the list', function () {
    actingAsRole('auditor', ['audit.view']);
    Audit::record('login');
    Audit::record('logout');

    $this->getJson('/api/v1/audit-logs?event=login')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.event', 'login');
    $this->getJson('/api/v1/audit-logs?from=not-a-date')->assertUnprocessable()->assertJsonStructure(['error' => ['details' => ['from']]]);
    $this->getJson('/api/v1/audit-logs')->assertOk()->assertJsonStructure(['data', 'links', 'meta']);

    $id = AuditLog::where('event', 'login')->value('id');
    $this->getJson("/api/v1/audit-logs/{$id}")->assertOk()->assertJsonPath('data.event', 'login');
});

it('seeds its menu entry idempotently', function () {
    $this->seed(AuditModuleSeeder::class);
    $this->seed(AuditModuleSeeder::class);

    expect(Menu::where('permission_name', 'audit.view')->count())->toBe(1);
});
