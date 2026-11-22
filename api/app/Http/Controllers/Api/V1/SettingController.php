<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateSettingRequest;
use App\Http\Resources\Api\V1\SettingResource;
use App\Models\Setting;
use App\Support\Settings;
use Illuminate\Support\Facades\Gate;

class SettingController extends Controller
{
    /**
     * Show settings (public)
     *
     * Only the settings declared public in config/settings.php. No authentication; secrets are never included.
     */
    public function publicShow(): SettingResource
    {
        return new SettingResource(publicOnly: true);
    }

    /**
     * Show settings
     *
     * Every declared setting with its default filled in. Secrets are write-only: `secrets_set` says whether each has a value.
     */
    public function show(): SettingResource
    {
        Gate::authorize('viewAny', Setting::class);

        return new SettingResource;
    }

    /**
     * Update settings
     *
     * Partial update; only the keys sent are changed. `null` resets a setting to its default (a secret to unset).
     * Logo and favicon have their own upload endpoints.
     */
    public function update(UpdateSettingRequest $request): SettingResource
    {
        Settings::set($request->validated());

        return new SettingResource;
    }
}
