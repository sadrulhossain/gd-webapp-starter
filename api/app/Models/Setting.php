<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** Site-wide settings. Singleton: one row (id 1), edit-only. Use Setting::current(). */
#[Fillable([
    'site_name', 'tagline', 'logo', 'favicon', 'currency', 'currency_symbol', 'address',
    'phone', 'email', 'hours', 'social_links',
])]
class Setting extends Model
{
    /** The id is the fixed 1, not auto-increment (see the settings migration). */
    public $incrementing = false;

    public const DAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    public const SOCIAL = ['facebook', 'instagram', 'x', 'linkedin', 'youtube', 'tiktok', 'whatsapp'];

    public static function current(): self
    {
        return static::query()->find(1)
            ?? static::unguarded(fn () => static::query()->create(['id' => 1] + static::defaults()));
    }

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        $open = ['open' => '09:00', 'close' => '17:00', 'closed' => false];
        $closed = ['open' => null, 'close' => null, 'closed' => true];

        return [
            'site_name' => 'My Business',
            'tagline' => 'Welcome',
            'currency' => 'USD',
            'currency_symbol' => '$',
            'hours' => array_combine(self::DAYS, [$open, $open, $open, $open, $open, $closed, $closed]),
            'social_links' => [],
        ];
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['hours' => 'array', 'social_links' => 'array'];
    }
}
