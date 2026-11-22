<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/** One audit entry. Append-only: the model refuses updates and deletes, and a database trigger backs that up. Write through App\Support\Audit. */
#[Fillable(['user_id', 'user_label', 'event', 'auditable_type', 'auditable_id', 'auditable_label', 'old_values', 'new_values', 'ip'])]
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['old_values' => 'array', 'new_values' => 'array', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('The audit log is append-only.'));
        static::deleting(fn () => throw new LogicException('The audit log is append-only.'));
    }
}
