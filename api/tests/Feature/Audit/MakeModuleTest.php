<?php

/** The generator inserts code above these markers and reads these stubs; removing either breaks `make:module`. */
it('keeps the scaffold markers and stubs in place', function () {
    expect(file_get_contents(config_path('permissions.php')))->toContain('// <make:module permissions>')
        ->and(file_get_contents(base_path('routes/api.php')))->toContain('// <make:module routes>')
        ->and(file_get_contents(database_path('seeders/DatabaseSeeder.php')))->toContain('// <make:module seeders>');

    foreach (['model', 'migration', 'policy', 'store-request', 'update-request', 'resource', 'controller', 'seeder', 'test'] as $stub) {
        expect(base_path("stubs/module/{$stub}.stub"))->toBeFile();
    }
});

it('rejects a bad module name', function () {
    $this->artisan('make:module', ['name' => 'not valid'])->assertFailed();
});
