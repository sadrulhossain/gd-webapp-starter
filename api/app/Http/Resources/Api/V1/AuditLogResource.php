<?php

namespace App\Http\Resources\Api\V1;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AuditLog */
class AuditLogResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event' => $this->event,
            'user_id' => $this->user_id,
            'user' => $this->user_label,
            'auditable_type' => $this->auditable_type,
            'auditable_id' => $this->auditable_id,
            'auditable' => $this->auditable_label,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'ip' => $this->ip,
            'created_at' => $this->created_at,
        ];
    }
}
