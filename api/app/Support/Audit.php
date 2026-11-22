<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** The one way to write an audit entry. Models get it for free through the Auditable trait; call record() for changes that bypass model events. */
final class Audit
{
    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     * @param  list<string>  $redact  extra attribute names to redact for this entry
     */
    public static function record(string $event, ?Model $subject = null, array $old = [], array $new = [], array $redact = []): AuditLog
    {
        $actor = auth('api')->user() ?? auth()->user();

        return AuditLog::query()->create([
            'user_id' => $actor?->getAuthIdentifier(),
            'user_label' => $actor instanceof User ? $actor->email : null,
            'event' => $event,
            'auditable_type' => $subject === null ? null : class_basename($subject),
            'auditable_id' => $subject?->getKey(),
            'auditable_label' => $subject === null ? null : self::label($subject),
            'old_values' => $old === [] ? null : self::redact($old, $redact),
            'new_values' => $new === [] ? null : self::redact($new, $redact),
            'ip' => request()->ip(),
        ]);
    }

    /**
     * Replaces sensitive values (config/audit.php names, plus any extras) with the redaction marker.
     *
     * @param  array<string, mixed>  $values
     * @param  list<string>  $extra
     * @return array<string, mixed>
     */
    public static function redact(array $values, array $extra = []): array
    {
        foreach ($values as $key => $value) {
            $sensitive = in_array($key, $extra, true);

            foreach ((array) config('audit.redact') as $needle) {
                $sensitive = $sensitive || str_contains(strtolower((string) $key), strtolower((string) $needle));
            }

            if ($sensitive && $value !== null) {
                $values[$key] = config('audit.redacted');
            }
        }

        return $values;
    }

    private static function label(Model $subject): string
    {
        foreach (['email', 'name', 'title', 'key'] as $attribute) {
            if (is_string($subject->getAttribute($attribute)) && $subject->getAttribute($attribute) !== '') {
                return $subject->getAttribute($attribute);
            }
        }

        return class_basename($subject).' #'.$subject->getKey();
    }
}
