<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;

/** Reads and writes settings declared in config/settings.php. Unedited settings fall back to their default. */
final class Settings
{
    /** @return array<string, array<string, mixed>> */
    public static function definitions(): array
    {
        return config('settings.settings');
    }

    public static function isSecret(string $key): bool
    {
        return (self::definitions()[$key]['type'] ?? null) === 'secret';
    }

    /** One value; a secret comes back decrypted (server-side use only, never in an API response). */
    public static function get(string $key): mixed
    {
        return self::all(secrets: true)[$key] ?? null;
    }

    /**
     * Every declared setting with defaults filled in. Secrets are left out unless asked for;
     * a secret is never part of the public view.
     *
     * @return array<string, mixed>
     */
    public static function all(bool $secrets = false, bool $publicOnly = false): array
    {
        $stored = Setting::query()->pluck('value', 'key');
        $out = [];

        foreach (self::definitions() as $key => $def) {
            $secret = $def['type'] === 'secret';

            if (($secret && (! $secrets || $publicOnly)) || ($publicOnly && ! ($def['public'] ?? false))) {
                continue;
            }

            $out[$key] = isset($stored[$key]) ? self::decode($def, $stored[$key]) : ($def['default'] ?? null);
        }

        return $out;
    }

    /** @return array<string, bool> secret key => has a value */
    public static function secretsSet(): array
    {
        $keys = array_keys(array_filter(self::definitions(), fn (array $def) => $def['type'] === 'secret'));
        $stored = Setting::query()->whereIn('key', $keys)->pluck('key')->all();

        return collect($keys)->mapWithKeys(fn (string $key) => [$key => in_array($key, $stored, true)])->all();
    }

    /** @param  array<string, mixed>  $values  only declared keys are written; null or '' removes the stored value */
    public static function set(array $values): void
    {
        foreach (array_intersect_key($values, self::definitions()) as $key => $value) {
            if ($value === null || $value === '') {
                Setting::query()->find($key)?->delete(); // through the model, so the change is audited

                continue;
            }

            $payload = json_encode($value);

            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => self::definitions()[$key]['type'] === 'secret' ? Crypt::encryptString($payload) : $payload],
            );
        }
    }

    /** @param  array<string, mixed>  $def */
    private static function decode(array $def, string $stored): mixed
    {
        return json_decode($def['type'] === 'secret' ? Crypt::decryptString($stored) : $stored, true);
    }
}
