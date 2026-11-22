<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** One stored setting value. Read and write through App\Support\Settings, never directly. */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    protected $table = 'setting_values';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';
}
