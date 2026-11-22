<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;

/**
 * Base class for module seeders. Permissions and default role grants are declared in
 * config/permissions.php (applied here through `permissions:sync`); a module seeder adds
 * its menu rows. Register it in DatabaseSeeder.
 *
 * Menu node: ['title' => string, 'permission' => ?string, 'path' => ?string,
 *             'icon' => ?string, 'sort' => ?int, 'children' => list<node>]
 * A node without 'permission' is a group.
 */
abstract class ModuleSeeder extends Seeder
{
    public const GUARD = PermissionCatalog::GUARD;

    /** @return list<array<string, mixed>> */
    abstract protected function menus(): array;

    public function run(): void
    {
        PermissionCatalog::sync();

        $this->seedMenus($this->menus(), null);
    }

    /** @param  list<array<string, mixed>>  $nodes */
    private function seedMenus(array $nodes, ?int $parentId): void
    {
        foreach ($nodes as $i => $node) {
            $permission = $node['permission'] ?? null;

            $menu = Menu::query()->updateOrCreate(
                $permission !== null
                    ? ['permission_name' => $permission]
                    : ['permission_name' => null, 'parent_id' => $parentId, 'title' => $node['title']],
                [
                    'parent_id' => $parentId,
                    'title' => $node['title'],
                    'path' => $node['path'] ?? null,
                    'icon' => $node['icon'] ?? null,
                    'sort' => $node['sort'] ?? ($i + 1) * 10,
                ],
            );

            $this->seedMenus($node['children'] ?? [], $menu->id);
        }
    }
}
