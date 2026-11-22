<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Menu;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Menu::class) ?? false;
    }

    /**
     * A group has no permission and no path. A link needs both; the actor must hold the permission (no escalation).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', Rule::exists('menus', 'id')->whereNull('permission_name')],
            'title' => ['required', 'string', 'max:120'],
            'icon' => ['nullable', 'string', 'max:60'],
            'active' => ['sometimes', 'boolean'],
            'permission_name' => [
                'nullable', 'string', Rule::in(PermissionCatalog::names()->all()), 'unique:menus,permission_name',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value !== null && ! $this->user()?->can($value)) {
                        $fail('You can only use a permission you hold.');
                    }
                },
            ],
            'path' => ['nullable', 'string', 'max:255', 'regex:#^/[A-Za-z0-9/_\-]*$#', 'required_with:permission_name', Rule::prohibitedIf(fn () => $this->input('permission_name') === null)],
        ];
    }
}
