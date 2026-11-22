<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Setting;
use App\Services\MediaService;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The declared settings as one flat object. Images are described, empty json maps stay objects,
 * secrets never appear: `secrets_set` only says whether each one has a value.
 */
class SettingResource extends JsonResource
{
    public function __construct(private readonly bool $publicOnly = false)
    {
        parent::__construct(null);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $out = [];

        foreach (Settings::all(publicOnly: $this->publicOnly) as $key => $value) {
            $type = Settings::definitions()[$key]['type'];

            $out[$key] = match (true) {
                $type === 'image' => app(MediaService::class)->describe($value),
                $type === 'json' && is_array($value) && ($value === [] || ! array_is_list($value)) => (object) $value,
                default => $value,
            };
        }

        if (! $this->publicOnly && ($secrets = Settings::secretsSet()) !== []) {
            $out['secrets_set'] = $secrets;
        }

        $out['updated_at'] = Setting::query()->max('updated_at');

        return $out;
    }
}
