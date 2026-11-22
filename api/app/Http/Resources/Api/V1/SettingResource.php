<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Setting;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Setting */
class SettingResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'site_name' => $this->site_name,
            'tagline' => $this->tagline,
            'currency' => $this->currency,
            'currency_symbol' => $this->currency_symbol,
            'logo' => app(MediaService::class)->describe($this->logo),
            'favicon' => app(MediaService::class)->describe($this->favicon),
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'hours' => $this->hours,
            'social_links' => (object) ($this->social_links ?? []),
            'updated_at' => $this->updated_at,
        ];
    }
}
