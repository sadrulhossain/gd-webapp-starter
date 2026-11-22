<?php

namespace Database\Seeders;

/** Defaults live in config/settings.php and are read until a setting is edited, so there is nothing to seed but the menu. */
class SettingsModuleSeeder extends ModuleSeeder
{
    protected function menus(): array
    {
        return [
            ['title' => 'Access', 'sort' => 80, 'children' => [
                ['title' => 'Settings', 'permission' => 'settings.view', 'path' => '/settings', 'icon' => 'settings', 'sort' => 40],
            ]],
        ];
    }
}
