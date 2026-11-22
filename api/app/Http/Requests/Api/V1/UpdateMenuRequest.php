<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Menu;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('menu')) ?? false;
    }

    /**
     * Title, icon and visibility for every row. Path and permission belong to the module that seeded the row,
     * so only custom rows may change them.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Menu $menu */
        $menu = $this->route('menu');
        $rules = [
            'title' => ['sometimes', 'required', 'string', 'max:120'],
            'icon' => ['sometimes', 'nullable', 'string', 'max:60'],
            'active' => ['sometimes', 'boolean'],
        ];

        if ($menu->custom && $menu->permission_name !== null) {
            $rules['permission_name'] = [
                'sometimes', 'required', 'string', Rule::in(PermissionCatalog::names()->all()),
                Rule::unique('menus', 'permission_name')->ignore($menu->id),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $this->user()?->can($value)) {
                        $fail('You can only use a permission you hold.');
                    }
                },
            ];
            $rules['path'] = ['sometimes', 'required', 'string', 'max:255', 'regex:#^/[A-Za-z0-9/_\-]*$#'];
        }

        return $rules;
    }
}
