<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Role;

/** @mixin Role */
class RoleResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_system' => in_array($this->name, config('permissions.system_roles'), true),
            'users_count' => $this->whenCounted('users'),
            'permissions' => $this->permissions->pluck('name')->sort()->values(),
        ];
    }
}
