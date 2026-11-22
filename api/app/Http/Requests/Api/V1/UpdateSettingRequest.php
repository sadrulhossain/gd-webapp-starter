<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Setting;
use App\Support\Settings;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    /** Authorization lives in the Policy; it runs before validation, so a 403 never leaks validation details. */
    public function authorize(): bool
    {
        return $this->user()?->can('update', Setting::class) ?? false;
    }

    /**
     * Built from config/settings.php: every declared, non-image setting is optional (PATCH), validated by its own rules.
     * Anything undeclared is ignored.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [];

        foreach (Settings::definitions() as $key => $def) {
            if ($def['type'] === 'image') {
                continue; // changed only through the upload endpoint
            }

            $rules[$key] = ['sometimes', ...($def['rules'] ?? [])];

            foreach ($def['nested'] ?? [] as $path => $nested) {
                $rules[$path] = $nested;
            }
        }

        return $rules;
    }
}
