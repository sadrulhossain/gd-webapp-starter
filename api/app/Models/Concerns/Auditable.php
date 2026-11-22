<?php

namespace App\Models\Concerns;

use App\Support\Audit;

/**
 * Every module model uses this: create, update and delete are written to the audit log with the
 * actor and IP. Sensitive attributes are redacted (config/audit.php); a model can name more by
 * defining `auditRedacted(): array`. Bulk query updates bypass model events: call Audit::record().
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn ($model) => Audit::record('created', $model, [], $model->getAttributes(), $model->auditRedactedNames()));

        static::updated(function ($model): void {
            $changes = array_diff_key($model->getChanges(), array_flip(config('audit.ignore')));

            if ($changes === []) {
                return;
            }

            Audit::record('updated', $model, array_intersect_key($model->getOriginal(), $changes), $changes, $model->auditRedactedNames());
        });

        static::deleted(fn ($model) => Audit::record('deleted', $model, $model->getOriginal(), [], $model->auditRedactedNames()));
    }

    /** @return list<string> */
    public function auditRedactedNames(): array
    {
        return method_exists($this, 'auditRedacted') ? $this->auditRedacted() : [];
    }
}
