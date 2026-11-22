<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** One stored setting value. Read and write through App\Support\Settings, never directly. */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    use Auditable;

    protected $table = 'setting_values';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @return list<string> a secret's value never reaches the audit log */
    public function auditRedacted(): array
    {
        return Settings::isSecret((string) $this->getKey()) ? ['value'] : [];
    }
}
