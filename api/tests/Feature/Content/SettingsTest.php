<?php

use App\Support\Settings;
use Database\Seeders\SettingsModuleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => $this->seed(SettingsModuleSeeder::class));

it('requires authentication', function () {
    $this->getJson('/api/v1/settings')->assertUnauthorized();
});

it('forbids reading without settings.view', function () {
    actingAsRole('nobody');

    $this->getJson('/api/v1/settings')->assertForbidden()->assertJsonPath('error.code', 'forbidden');
});

it('shows the seeded defaults', function () {
    actingAsRole('viewer', ['settings.view']);

    $this->getJson('/api/v1/settings')
        ->assertOk()
        ->assertJsonPath('data.site_name', 'My Business')
        ->assertJsonPath('data.hours.mon.open', '09:00')
        ->assertJsonPath('data.hours.sun.closed', true)
        ->assertJsonPath('data.social_links', []);
});

it('forbids updating with view-only access, before validating', function () {
    actingAsRole('viewer', ['settings.view']);

    $this->putJson('/api/v1/settings', ['email' => 'not-an-email'])->assertForbidden();
});

it('updates the name, hours and social links', function () {
    actingAsRole('editor', ['settings.view', 'settings.update']);

    $this->patchJson('/api/v1/settings', [
        'site_name' => 'Acme',
        'email' => 'hello@acme.test',
        'hours' => ['mon' => ['open' => '08:30', 'close' => '18:00', 'closed' => false]],
        'social_links' => ['facebook' => 'https://facebook.com/acme'],
    ])
        ->assertOk()
        ->assertJsonPath('data.site_name', 'Acme')
        ->assertJsonPath('data.social_links.facebook', 'https://facebook.com/acme');

    expect(Settings::get('tagline'))->toBe('Welcome') // untouched by a partial update
        ->and(Settings::get('hours'))->toBe(['mon' => ['open' => '08:30', 'close' => '18:00', 'closed' => false]]);
});

it('rejects invalid input', function () {
    actingAsRole('editor', ['settings.view', 'settings.update']);

    $response = $this->putJson('/api/v1/settings', [
        'site_name' => '',
        'email' => 'nope',
        'hours' => ['funday' => ['closed' => true]],
        'social_links' => ['facebook' => 'not a url'],
    ])->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');

    expect(array_keys($response->json('error.details')))
        ->toEqualCanonicalizing(['site_name', 'email', 'hours', 'social_links.facebook']);
});

it('is edit-only', function () {
    actingAsRole('editor', ['settings.view', 'settings.update']);

    $this->postJson('/api/v1/settings', [])->assertStatus(405);
    $this->deleteJson('/api/v1/settings')->assertStatus(405);

});

it('uploads, replaces and removes the logo and favicon', function () {
    Storage::fake('uploads');
    actingAsRole('editor', ['settings.view', 'settings.update']);
    $accept = ['Accept' => 'application/json'];

    foreach (['logo', 'favicon'] as $kind) {
        $this->post("/api/v1/settings/{$kind}", ['image' => UploadedFile::fake()->image('a.png', 200, 200)], $accept)
            ->assertOk()
            ->assertJsonStructure(['data' => [$kind => ['url', 'webp']]]);
        $path = Settings::get($kind);
        expect($path)->toMatch('#^site/[a-z0-9]{32}/original\.png$#');

        $this->deleteJson("/api/v1/settings/{$kind}")->assertOk()->assertJsonPath("data.{$kind}", null);
    }

    expect(Storage::disk('uploads')->allFiles())->toBe([]);

    actingAsRole('viewer', ['settings.view']);
    $this->post('/api/v1/settings/logo', ['image' => UploadedFile::fake()->image('a.png')], $accept)->assertForbidden();
});

it('accepts an .ico favicon but not as a logo', function () {
    Storage::fake('uploads');
    actingAsRole('editor', ['settings.view', 'settings.update']);
    $accept = ['Accept' => 'application/json'];

    $this->post('/api/v1/settings/favicon', ['image' => UploadedFile::fake()->create('favicon.ico', 1, 'image/vnd.microsoft.icon')], $accept)->assertOk();
    expect(Settings::get('favicon'))->toMatch('#^site/[a-z0-9]{32}/original\.ico$#');

    $this->post('/api/v1/settings/logo', ['image' => UploadedFile::fake()->create('logo.ico', 1, 'image/vnd.microsoft.icon')], $accept)->assertUnprocessable();
});

it('serves only public settings without authentication', function () {
    config(['settings.settings.internal_note' => ['type' => 'string', 'default' => 'x', 'public' => false, 'rules' => ['nullable', 'string']]]);

    $keys = array_keys($this->getJson('/api/v1/public/settings')->assertOk()->json('data'));
    expect($keys)->toContain('site_name', 'currency')->not->toContain('internal_note', 'secrets_set');

    actingAsRole('viewer', ['settings.view']);
    $this->getJson('/api/v1/settings')->assertJsonPath('data.internal_note', 'x');
});

it('stores secrets encrypted and never returns them', function () {
    config(['settings.settings.smtp_password' => ['type' => 'secret', 'default' => null, 'public' => true, 'rules' => ['nullable', 'string', 'max:200']]]);
    actingAsRole('editor', ['settings.view', 'settings.update']);

    $this->getJson('/api/v1/settings')->assertJsonPath('data.secrets_set.smtp_password', false);

    $response = $this->patchJson('/api/v1/settings', ['smtp_password' => 'hunter2'])->assertOk()
        ->assertJsonPath('data.secrets_set.smtp_password', true);
    expect($response->getContent())->not->toContain('hunter2')
        ->and(array_keys($response->json('data')))->not->toContain('smtp_password');

    $raw = DB::table('setting_values')->where('key', 'smtp_password')->value('value');
    expect($raw)->not->toContain('hunter2')->and(Settings::get('smtp_password'))->toBe('hunter2');

    // A secret is never public, even when declared so.
    expect($this->getJson('/api/v1/public/settings')->getContent())->not->toContain('hunter2')->not->toContain('smtp_password');

    $this->patchJson('/api/v1/settings', ['smtp_password' => null])->assertJsonPath('data.secrets_set.smtp_password', false);
    expect(Settings::get('smtp_password'))->toBeNull();
});

it('resets a setting to its default with null and ignores undeclared or image keys', function () {
    actingAsRole('editor', ['settings.view', 'settings.update']);

    $this->patchJson('/api/v1/settings', ['tagline' => 'Hi', 'logo' => 'x/y.png', 'bogus' => 1])->assertOk();
    expect(Settings::get('tagline'))->toBe('Hi')->and(Settings::get('logo'))->toBeNull()->and(DB::table('setting_values')->where('key', 'bogus')->exists())->toBeFalse();

    $this->patchJson('/api/v1/settings', ['tagline' => null])->assertOk()->assertJsonPath('data.tagline', 'Welcome');
});
