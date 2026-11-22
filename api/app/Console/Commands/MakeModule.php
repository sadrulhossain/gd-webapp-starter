<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Scaffolds a module so it inherits the invariants: permissions declared in config/permissions.php,
 * a policy, a menu entry (seeded), the Auditable trait and a test file. Edit the generated
 * migration, model, requests and resource to add the real fields.
 */
class MakeModule extends Command
{
    protected $signature = 'make:module {name : Singular StudlyCase model name, e.g. Product}';

    protected $description = 'Scaffold a module: model, migration, policy, requests, resource, controller, routes, permissions, menu seeder and tests';

    public function handle(): int
    {
        $model = (string) $this->argument('name');

        if (! preg_match('/^[A-Z][A-Za-z0-9]*$/', $model)) {
            $this->error('The name must be StudlyCase, e.g. Product.');

            return self::FAILURE;
        }

        $plural = Str::plural(Str::snake($model));
        $vars = [
            '{{Model}}' => $model,
            '{{model}}' => Str::camel($model),
            '{{plural}}' => $plural,
            '{{route}}' => str_replace('_', '-', $plural),
            '{{Title}}' => Str::headline(Str::plural($model)),
            '{{Singular}}' => Str::lower(Str::headline($model)),
        ];

        $files = [
            'model' => app_path("Models/{$model}.php"),
            'policy' => app_path("Policies/{$model}Policy.php"),
            'store-request' => app_path("Http/Requests/Api/V1/Store{$model}Request.php"),
            'update-request' => app_path("Http/Requests/Api/V1/Update{$model}Request.php"),
            'resource' => app_path("Http/Resources/Api/V1/{$model}Resource.php"),
            'controller' => app_path("Http/Controllers/Api/V1/{$model}Controller.php"),
            'seeder' => database_path("seeders/{$model}ModuleSeeder.php"),
            'test' => base_path("tests/Feature/{$model}/{$model}Test.php"),
            'migration' => database_path('migrations/'.date('Y_m_d_His')."_create_{$plural}_table.php"),
        ];

        $permissions = config_path('permissions.php');
        $routes = base_path('routes/api.php');
        $seeders = database_path('seeders/DatabaseSeeder.php');

        foreach ($files as $path) {
            if (File::exists($path)) {
                $this->error("Refusing to overwrite {$path}");

                return self::FAILURE;
            }
        }

        if (str_contains(File::get($permissions), "'{$plural}' => [")) {
            $this->error("The permission module '{$plural}' already exists.");

            return self::FAILURE;
        }

        foreach (['permissions' => $permissions, 'routes' => $routes, 'seeders' => $seeders] as $marker => $path) {
            if (! str_contains(File::get($path), "// <make:module {$marker}>")) {
                $this->error("Marker '// <make:module {$marker}>' is missing in {$path}");

                return self::FAILURE;
            }
        }

        foreach ($files as $stub => $path) {
            File::ensureDirectoryExists(dirname($path));
            File::put($path, strtr(File::get(base_path("stubs/module/{$stub}.stub")), $vars));
        }

        $this->insertBeforeMarker($permissions, 'permissions', strtr(implode("\n", [
            "'{{plural}}' => [",
            "    'view' => 'View {{Title}}',",
            "    'create' => 'Create {{Title}}',",
            "    'update' => 'Edit {{Title}}',",
            "    'delete' => 'Delete {{Title}}',",
            '],',
        ]), $vars));
        $this->insertBeforeMarker($seeders, 'seeders', "{$model}ModuleSeeder::class,");
        $this->insertBeforeMarker($routes, 'routes', "Route::apiResource('{$vars['{{route}}']}', {$model}Controller::class);");
        $this->addRouteImport($routes, "use App\\Http\\Controllers\\Api\\V1\\{$model}Controller;");

        $this->info("Module {$model} scaffolded: {$plural}.view|create|update|delete, route /api/v1/{$vars['{{route}}']}.");
        $this->line('Next: edit the migration, model, requests and resource; run migrate and `permissions:sync`; run `pint`; run `pest --filter='.$model.'`.');

        return self::SUCCESS;
    }

    private function insertBeforeMarker(string $path, string $marker, string $code): void
    {
        $contents = File::get($path);
        $needle = "// <make:module {$marker}>";
        $position = strpos($contents, $needle);

        // Insert whole lines above the marker line, each with the marker line's own indentation.
        $lineStart = strrpos(substr($contents, 0, $position), "\n") + 1;
        $indent = substr($contents, $lineStart, $position - $lineStart);
        $block = implode("\n", array_map(fn (string $line) => $indent.$line, explode("\n", $code)))."\n";

        File::put($path, substr($contents, 0, $lineStart).$block.substr($contents, $lineStart));
    }

    /** Inserts the import in alphabetical position among the `use` lines, which Pint's ordered_imports expects. */
    private function addRouteImport(string $path, string $import): void
    {
        $lines = explode("\n", File::get($path));
        $at = null;

        foreach ($lines as $i => $line) {
            if (str_starts_with($line, 'use ')) {
                $at ??= $i;
                if (strcasecmp($line, $import) > 0) {
                    $at = $i;
                    break;
                }
                $at = $i + 1;
            }
        }

        array_splice($lines, $at ?? 0, 0, [$import]);
        File::put($path, implode("\n", $lines));
    }
}
