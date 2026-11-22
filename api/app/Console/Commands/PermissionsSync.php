<?php

namespace App\Console\Commands;

use App\Support\PermissionCatalog;
use Illuminate\Console\Command;

class PermissionsSync extends Command
{
    protected $signature = 'permissions:sync {--prune : Delete permissions that are no longer declared in config/permissions.php}';

    protected $description = 'Create the permissions declared in config/permissions.php and grant the default role permissions';

    public function handle(): int
    {
        $result = PermissionCatalog::sync((bool) $this->option('prune'));

        $this->info(count($result['created']).' permission(s) created');

        if ($result['stale'] !== []) {
            $this->warn(($result['pruned'] ? 'pruned: ' : 'undeclared (use --prune to delete): ').implode(', ', $result['stale']));
        }

        return self::SUCCESS;
    }
}
