<?php

namespace Database\Seeders;

/** Reference module seeder: copy this shape for every new module. */
class DashboardModuleSeeder extends ModuleSeeder
{
    protected function menus(): array
    {
        return [
            ['title' => 'Dashboard', 'permission' => 'dashboard.view', 'path' => '/', 'icon' => 'home', 'sort' => 0],
        ];
    }
}
