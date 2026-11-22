<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
            // Each module seeds its own permissions + menu rows. Add new module seeders here.
            DashboardModuleSeeder::class,
            SettingsModuleSeeder::class,
            AccessModuleSeeder::class,
            AuditModuleSeeder::class,
            // <make:module seeders> new module seeders are added above this line
            SuperadminSeeder::class,
            OauthClientSeeder::class,
        ]);
    }
}
