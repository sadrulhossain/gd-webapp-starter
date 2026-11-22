<?php

namespace Database\Seeders;

use App\Models\Setting;

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

    public function run(): void
    {
        parent::run();

        Setting::current(); // creates the default row once; never overwrites edits
    }
}
