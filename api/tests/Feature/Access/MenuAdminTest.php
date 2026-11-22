<?php

use App\Models\Menu;
use Database\Seeders\DashboardModuleSeeder;

beforeEach(function () {
    $this->seed([DashboardModuleSeeder::class, AccessModuleSeeder::class]);
});

it('requires authentication and permissions', function () {
    $this->getJson('/api/v1/menus')->assertUnauthorized();
    $this->putJson('/api/v1/menus/reorder', ['ids' => [1]])->assertUnauthorized();

    actingAsRole('nobody');
    $this->getJson('/api/v1/menus')->assertForbidden();
    $this->putJson('/api/v1/menus/reorder', ['ids' => [1]])->assertForbidden();
});

it('lists the whole tree depth-first with depth', function () {
    actingAsRole('viewer', ['menus.view']);

    $rows = $this->getJson('/api/v1/menus')->assertOk()->json('data');
    $group = collect($rows)->firstWhere('title', 'Access');
    $child = collect($rows)->firstWhere('title', 'Users');

    expect($group['depth'])->toBe(0)->and($child['depth'])->toBe(1)->and($child['parent_id'])->toBe($group['id']);
});

it('edits title and icon only', function () {
    actingAsRole('editor', ['menus.update']);
    $menu = Menu::where('path', '/users')->firstOrFail();

    $this->patchJson("/api/v1/menus/{$menu->id}", ['title' => 'People', 'icon' => 'box', 'path' => '/hacked'])
        ->assertOk()->assertJsonPath('data.title', 'People');

    expect($menu->refresh()->path)->toBe('/users');
    $this->patchJson("/api/v1/menus/{$menu->id}", ['title' => ''])->assertUnprocessable()->assertJsonStructure(['error' => ['details' => ['title']]]);
});

it('reorders siblings and rejects mixed parents', function () {
    actingAsRole('editor', ['menus.update']);
    $top = Menu::whereNull('parent_id')->orderBy('sort')->pluck('id')->all();
    $child = Menu::where('path', '/users')->value('id');

    $this->putJson('/api/v1/menus/reorder', ['ids' => array_reverse($top)])->assertOk();
    expect(Menu::whereNull('parent_id')->orderBy('sort')->pluck('id')->all())->toBe(array_reverse($top));

    $this->putJson('/api/v1/menus/reorder', ['ids' => [$top[0], $child]])->assertUnprocessable()->assertJsonStructure(['error' => ['details' => ['ids']]]);
    $this->putJson('/api/v1/menus/reorder', ['ids' => [999999]])->assertUnprocessable()->assertJsonStructure(['error' => ['details' => ['ids.0']]]);
});

it('hides inactive entries and their children from /me/menus', function () {
    $user = actingAsRole('editor', ['menus.update', 'users.view']);
    $group = Menu::where('title', 'Access')->firstOrFail();

    $titles = fn () => collect($this->getJson('/api/v1/me/menus')->assertOk()->json('data'))->pluck('title');
    expect($titles()->all())->toContain('Access');

    $this->patchJson("/api/v1/menus/{$group->id}", ['active' => false])->assertOk()->assertJsonPath('data.active', false);
    expect($titles()->all())->not->toContain('Access');
});

it('guards creating custom entries', function () {
    $payload = ['title' => 'Docs', 'permission_name' => 'roles.create', 'path' => '/docs'];

    $this->postJson('/api/v1/menus', $payload)->assertUnauthorized();

    actingAsRole('reader', ['menus.view']);
    $this->postJson('/api/v1/menus', $payload)->assertForbidden();

    // No escalation: the actor does not hold roles.create.
    actingAsRole('builder', ['menus.create']);
    $this->postJson('/api/v1/menus', $payload)->assertUnprocessable()->assertJsonStructure(['error' => ['details' => ['permission_name']]]);
    // A permission already used by a seeded row, an unknown one, and a path without a permission.
    $this->postJson('/api/v1/menus', [...$payload, 'permission_name' => 'users.view'])->assertUnprocessable();
    $this->postJson('/api/v1/menus', [...$payload, 'permission_name' => 'made.up'])->assertUnprocessable();
    $this->postJson('/api/v1/menus', ['title' => 'Loose', 'path' => '/loose'])->assertUnprocessable()->assertJsonStructure(['error' => ['details' => ['path']]]);
    $this->postJson('/api/v1/menus', ['title' => ''])->assertUnprocessable()->assertJsonStructure(['error' => ['details' => ['title']]]);
});

it('creates a custom link, edits it and deletes it, but never a seeded row', function () {
    actingAsRole('boss', ['menus.create', 'menus.update', 'menus.delete', 'roles.create']);
    $group = Menu::where('title', 'Access')->firstOrFail();

    $id = $this->postJson('/api/v1/menus', ['title' => 'Docs', 'permission_name' => 'roles.create', 'path' => '/docs', 'parent_id' => $group->id])
        ->assertCreated()->assertJsonPath('data.custom', true)->json('data.id');

    $this->patchJson("/api/v1/menus/{$id}", ['path' => '/handbook'])->assertOk()->assertJsonPath('data.path', '/handbook');
    $this->deleteJson("/api/v1/menus/{$id}")->assertNoContent();
    expect(Menu::find($id))->toBeNull();

    $seeded = Menu::where('path', '/users')->firstOrFail();
    $this->deleteJson("/api/v1/menus/{$seeded->id}")->assertUnprocessable();
    $this->deleteJson("/api/v1/menus/{$group->id}")->assertUnprocessable();
    expect(Menu::find($seeded->id))->not->toBeNull();
});

it('requires menus.delete to delete', function () {
    $menu = Menu::create(['title' => 'Tmp', 'custom' => true]);

    $this->deleteJson("/api/v1/menus/{$menu->id}")->assertUnauthorized();
    actingAsRole('nobody');
    $this->deleteJson("/api/v1/menus/{$menu->id}")->assertForbidden();
});
