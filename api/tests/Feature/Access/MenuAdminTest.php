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
