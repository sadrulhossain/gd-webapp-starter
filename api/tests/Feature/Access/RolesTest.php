<?php

use App\Models\User;
use App\Support\PermissionCatalog;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('requires authentication and roles.view', function () {
    $this->getJson('/api/v1/roles')->assertUnauthorized();
    $this->getJson('/api/v1/permissions')->assertUnauthorized();

    actingAsRole('nobody');
    $this->getJson('/api/v1/roles')->assertForbidden();
    $this->getJson('/api/v1/permissions')->assertForbidden();
});

it('lists roles without the superadmin and lists permission names', function () {
    actingAsRole('viewer', ['roles.view']);
    Role::findOrCreate(config('access.superadmin_role'), 'api');

    $names = collect($this->getJson('/api/v1/roles')->assertOk()->json('data'))->pluck('name');
    expect($names)->toContain('viewer')->not->toContain('superadmin');

    $this->getJson('/api/v1/permissions')->assertOk()->assertJsonPath('data.0', 'roles.view');
});

it('creates a role with permissions', function () {
    actingAsRole('manager', ['roles.create', 'faqs.view', 'faqs.update']);

    $this->postJson('/api/v1/roles', ['name' => 'editor', 'permissions' => ['faqs.view']])
        ->assertCreated()
        ->assertJsonPath('data.permissions.0', 'faqs.view');
});

it('rejects a duplicate, the superadmin name and a bad name', function () {
    actingAsRole('manager', ['roles.create']);

    $this->postJson('/api/v1/roles', ['name' => 'manager'])->assertUnprocessable()->assertJsonStructure(['error' => ['details' => ['name']]]);
    $this->postJson('/api/v1/roles', ['name' => 'superadmin'])->assertUnprocessable()->assertJsonStructure(['error' => ['details' => ['name']]]);
    $this->postJson('/api/v1/roles', ['name' => 'Bad Name'])->assertUnprocessable()->assertJsonStructure(['error' => ['details' => ['name']]]);
});

it('only lets a non-superadmin grant permissions they hold', function () {
    actingAsRole('manager', ['roles.create', 'roles.update', 'faqs.view']);
    Permission::findOrCreate('users.delete', 'api');
    $role = Role::findOrCreate('editor', 'api');

    $this->postJson('/api/v1/roles', ['name' => 'x', 'permissions' => ['users.delete']])
        ->assertUnprocessable()->assertJsonStructure(['error' => ['details' => ['permissions.0']]]);
    $this->patchJson("/api/v1/roles/{$role->id}", ['permissions' => ['faqs.view']])->assertOk();
});

it('syncs permissions on update', function () {
    actingAsRole('manager', ['roles.update', 'faqs.view', 'faqs.update']);
    $role = Role::findOrCreate('editor', 'api')->givePermissionTo(Permission::findOrCreate('faqs.view', 'api'));

    $this->patchJson("/api/v1/roles/{$role->id}", ['permissions' => ['faqs.update']])->assertOk();

    expect($role->refresh()->permissions->pluck('name')->all())->toBe(['faqs.update']);
});

it('never changes or deletes the superadmin role', function () {
    actingAsRole('manager', ['roles.update', 'roles.delete']);
    $root = Role::findOrCreate(config('access.superadmin_role'), 'api');

    $this->patchJson("/api/v1/roles/{$root->id}", ['name' => 'x'])->assertForbidden();
    $this->deleteJson("/api/v1/roles/{$root->id}")->assertForbidden();
});

it('deletes a role', function () {
    actingAsRole('manager', ['roles.delete']);
    $role = Role::findOrCreate('temp', 'api');

    $this->deleteJson("/api/v1/roles/{$role->id}")->assertNoContent();
    expect(Role::where('name', 'temp')->exists())->toBeFalse();
});

it('declares permissions only in config/permissions.php and syncs them', function () {
    $this->artisan('permissions:sync')->assertSuccessful();

    expect(Permission::where('guard_name', 'api')->pluck('name')->sort()->values()->all())
        ->toBe(collect(array_keys(PermissionCatalog::all()))->sort()->values()->all());
    expect(Role::findByName('admin', 'api')->hasPermissionTo('settings.update'))->toBeTrue();

    $this->artisan('permissions:sync')->assertSuccessful(); // idempotent
});

it('prunes undeclared permissions only with --prune', function () {
    Permission::findOrCreate('legacy.thing', 'api');

    $this->artisan('permissions:sync')->assertSuccessful();
    expect(Permission::where('name', 'legacy.thing')->exists())->toBeTrue();

    $this->artisan('permissions:sync --prune')->assertSuccessful();
    expect(Permission::where('name', 'legacy.thing')->exists())->toBeFalse();
});

it('lists permissions grouped by module with labels', function () {
    actingAsRole('viewer', ['roles.view']);
    $this->artisan('permissions:sync');

    $this->getJson('/api/v1/permissions')->assertOk()
        ->assertJsonPath('groups.users.0.name', 'users.create')
        ->assertJsonPath('groups.users.0.label', 'Create users');
});

it('refuses to delete a role that is still assigned, and a system role', function () {
    actingAsRole('manager', ['roles.delete']);
    $used = Role::findOrCreate('used', 'api');
    User::factory()->create()->assignRole($used);
    $admin = Role::findOrCreate('admin', 'api');

    $this->deleteJson("/api/v1/roles/{$used->id}")->assertStatus(409);
    $this->deleteJson("/api/v1/roles/{$admin->id}")->assertForbidden();
});

it('refuses to rename a system role', function () {
    actingAsRole('manager', ['roles.update']);
    $admin = Role::findOrCreate('admin', 'api');

    $this->patchJson("/api/v1/roles/{$admin->id}", ['name' => 'boss'])
        ->assertUnprocessable()->assertJsonStructure(['error' => ['details' => ['name']]]);
});

it('keeps permissions an editor does not hold when they replace a role\'s permissions', function () {
    actingAsRole('manager', ['roles.update', 'users.view']);
    $role = Role::findOrCreate('mixed', 'api');
    $role->givePermissionTo([Permission::findOrCreate('users.view', 'api'), Permission::findOrCreate('settings.update', 'api')]);

    $this->patchJson("/api/v1/roles/{$role->id}", ['permissions' => []])->assertOk();

    expect($role->refresh()->permissions->pluck('name')->all())->toBe(['settings.update']);
});

it('only lets a non-superadmin assign roles whose permissions they hold', function () {
    actingAsRole('manager', ['users.create', 'users.view']);
    Role::findOrCreate('small', 'api')->givePermissionTo(Permission::findOrCreate('users.view', 'api'));
    Role::findOrCreate('big', 'api')->givePermissionTo(Permission::findOrCreate('roles.delete', 'api'));

    $this->postJson('/api/v1/users', ['name' => 'A', 'email' => 'a@example.com', 'password' => 'a-long-password-1', 'roles' => ['big']])
        ->assertUnprocessable()->assertJsonStructure(['error' => ['details' => ['roles.0']]]);
    $this->postJson('/api/v1/users', ['name' => 'A', 'email' => 'a@example.com', 'password' => 'a-long-password-1', 'roles' => ['small']])
        ->assertCreated();
});
