<?php

namespace Database\Seeders;

/** The audit log menu entry. Not granted to `admin`: only the superadmin reads it until a role is given audit.view. */
class AuditModuleSeeder extends ModuleSeeder
{
    protected function menus(): array
    {
        return [
            ['title' => 'Access', 'sort' => 80, 'children' => [
                ['title' => 'Audit log', 'permission' => 'audit.view', 'path' => '/audit-log', 'icon' => 'history', 'sort' => 50],
            ]],
        ];
    }
}
