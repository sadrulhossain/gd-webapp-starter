<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSettingImageRequest;
use App\Http\Resources\Api\V1\SettingResource;
use App\Models\Setting;
use App\Services\MediaService;
use App\Support\Settings;
use Illuminate\Support\Facades\Gate;

class SettingImageController extends Controller
{
    /**
     * Upload the site logo
     *
     * Multipart field `image` (jpeg, png or webp). Replaces the current logo.
     */
    public function storeLogo(StoreSettingImageRequest $request, MediaService $media): SettingResource
    {
        return $this->store($request, $media, 'logo');
    }

    /** Remove the site logo */
    public function destroyLogo(MediaService $media): SettingResource
    {
        Gate::authorize('update', Setting::class);

        return $this->destroy($media, 'logo');
    }

    /**
     * Upload the favicon
     *
     * Multipart field `image` (ico, jpeg, png or webp), ideally square. Replaces the current favicon.
     */
    public function storeFavicon(StoreSettingImageRequest $request, MediaService $media): SettingResource
    {
        return $this->store($request, $media, 'favicon');
    }

    /** Remove the favicon */
    public function destroyFavicon(MediaService $media): SettingResource
    {
        Gate::authorize('update', Setting::class);

        return $this->destroy($media, 'favicon');
    }

    private function store(StoreSettingImageRequest $request, MediaService $media, string $key): SettingResource
    {
        $previous = Settings::get($key);

        Settings::set([$key => $media->store($request->file('image'), 'site', allowIcon: $key === 'favicon')]);
        $media->delete($previous);

        return new SettingResource;
    }

    private function destroy(MediaService $media, string $key): SettingResource
    {
        $media->delete(Settings::get($key));
        Settings::set([$key => null]);

        return new SettingResource;
    }
}
